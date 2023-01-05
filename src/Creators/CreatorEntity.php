<?php

namespace Nanvaie\DatabaseRepository\Creators;

use Illuminate\Support\Collection;
use Nanvaie\DatabaseRepository\CustomMySqlQueries;

class CreatorEntity implements IClassCreator
{
    use CustomMySqlQueries;
//    protected $columns=
    public function __construct(
        public Collection $columns,
        public string $attributeStub,
        public string $detectForeignKeys,
        public string $tableName,
        public string $entityName,
        public string $entityNamespace,
        public string $accessorsStub,
        public string $baseContent)
    {

    }

    public function createAttributs(Collection $columns, string $attributeStub,string $detectForeignKeys,string $tableName):array{
        $attributes = [];
        foreach ($columns as $_column) {
            $defaultValue = ($_column->COLUMN_DEFAULT ?? 'null') ? ($_column->COLUMN_DEFAULT ?? 'null') : "''";
            $attributes[] = [
                $_column->COLUMN_NAME,
                $this->writeAttribute(
                    $attributeStub,
                    $_column->COLUMN_NAME.($_column->IS_NULLABLE === 'YES' ? ' = '.$defaultValue : ''),
                    ($_column->IS_NULLABLE === 'YES' ? 'null|' : '') . $this->dataTypes[$_column->DATA_TYPE]
                )
            ];
        }

        if ($detectForeignKeys) {
            $foreignKeys = $this->extractForeignKeys($tableName);

            // Create Additional Attributes from Foreign Keys
            foreach ($foreignKeys as $_foreignKey) {
                $attributes[] = [
                    $_column->COLUMN_NAME,
                    $this->writeAttribute(
                        $attributeStub,
                        $_foreignKey->VARIABLE_NAME,
                        $_foreignKey->ENTITY_DATA_TYPE
                    )
                ];
            }
        }

        return $attributes;
    }


    public function createFunctions(Collection $columns, bool|string $accessorsStub,$detectForeignKeys,$tableName):array
    {
        $settersAndGetters = [];
        foreach ($columns as $_column) {
            $settersAndGetters[] =
                [
                    $_column->COLUMN_NAME,
                    $this->writeAccessors(
                        $accessorsStub,
                        $_column->COLUMN_NAME,
                        ($_column->IS_NULLABLE === 'YES' ? 'null|' : '') . $this->dataTypes[$_column->DATA_TYPE]
                    )
                ];
        }
        if ($detectForeignKeys) {
            $foreignKeys = $this->extractForeignKeys($tableName);

            // Create Additional Setters and Getters from Foreign keys
            foreach ($foreignKeys as $_foreignKey) {
                $settersAndGetters[] =
                    [
                        $_column->COLUMN_NAME,
                        $this->writeAccessors(
                            $accessorsStub,
                            $_foreignKey->VARIABLE_NAME,
                            $_foreignKey->ENTITY_DATA_TYPE
                        )
                    ];
            }
        }
        return $settersAndGetters;
    }


    private function writeAttribute(string $attributeStub, string $attributeName, string $attributeType): string
    {
        return str_replace(['{{ AttributeType }}', '{{ AttributeName }}'],
            [$attributeType, $attributeName],
            $attributeStub);
    }

    /**
     * Generate getter and setter for given attribute.
     */
    private function writeAccessors(string $accessorStub, string $attributeName, string $attributeType): string
    {
        return str_replace(['{{ AttributeType }}', '{{ AttributeName }}', '{{ GetterName }}', '{{ SetterName }}'],
            [$attributeType, $attributeName, ucfirst($attributeName), ucfirst($attributeName)],
            $accessorStub);
    }

}
