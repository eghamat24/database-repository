<?php

namespace Eghamat24\DatabaseRepository\Creators;

use Illuminate\Support\Collection;
use Eghamat24\DatabaseRepository\CustomMySqlQueries;

class CreatorEntity implements IClassCreator
{
    use CustomMySqlQueries;

    protected const PARENT_NAME = 'Entity';

    private const BOOL_TYPE = 'bool';

    public function __construct(
        public Collection $columns,
        public string     $detectForeignKeys,
        public string     $tableName,
        public string     $entityName,
        public string     $entityNamespace,
        public string     $entityStubsPath)
    {

    }

    public function getExtendSection(): string
    {
        return 'extends ' . self::PARENT_NAME;
    }

    public function createAttributes(): array
    {
        $columns = $this->columns;
        $entityStubsPath = $this->entityStubsPath;
        $detectForeignKeys = $this->detectForeignKeys;
        $tableName = $this->tableName;
        $attributes = [];

        foreach ($columns as $_column) {
            $dataType = $this->getDataType($_column->COLUMN_TYPE, $_column->DATA_TYPE);
            $defaultValue = ($_column->COLUMN_DEFAULT ?? 'null') ? ($_column->COLUMN_DEFAULT ?? 'null') : "''";

            $defaultValue = ($dataType == self::BOOL_TYPE) ? ((in_array($defaultValue, [0, '', "''"])) ? 'false' :
                ((in_array($defaultValue, [1, '1'])) ? 'true' : $defaultValue)) : $defaultValue;

            $attributes[$_column->COLUMN_NAME] =
                $this->writeAttribute(
                    $entityStubsPath,
                    $_column->COLUMN_NAME . (!in_array($_column->COLUMN_DEFAULT, [null, 'NULL']) ? ' = ' . $defaultValue : ''),
                    ($_column->IS_NULLABLE === 'YES' ? 'null|' : '') . $dataType
                );
        }

        if ($detectForeignKeys) {
            $foreignKeys = $this->extractForeignKeys($tableName);

            // Create Additional Attributes from Foreign Keys
            foreach ($foreignKeys as $_foreignKey) {
                $attributes[$_column->COLUMN_NAME] =
                    $this->writeAttribute(
                        $entityStubsPath,
                        $_foreignKey->VARIABLE_NAME,
                        $_foreignKey->ENTITY_DATA_TYPE
                    );
            }
        }

        return $attributes;
    }

    public function createUses(): array
    {
        return ["use Eghamat24\DatabaseRepository\Models\Entity\Entity;"];
    }

    public function createFunctions(): array
    {
        $columns = $this->columns;
        $entityStubsPath = $this->entityStubsPath;
        $detectForeignKeys = $this->detectForeignKeys;
        $tableName = $this->tableName;
        $settersAndGetters = [];
        foreach ($columns as $_column) {
            $dataType = $this->getDataType($_column->COLUMN_TYPE, $_column->DATA_TYPE);

            $settersAndGetters['get' . ucwords($_column->COLUMN_NAME)] =
                $this->writeAccessors(
                    $entityStubsPath,
                    $_column->COLUMN_NAME,
                    ($_column->IS_NULLABLE === 'YES' ? 'null|' : '') . $dataType,
                    'getter'
                );
            $settersAndGetters['set' . ucwords($_column->COLUMN_NAME)] =
                $this->writeAccessors(
                    $entityStubsPath,
                    $_column->COLUMN_NAME,
                    ($_column->IS_NULLABLE === 'YES' ? 'null|' : '') . $dataType,
                    'setter'
                );

        }
        if ($detectForeignKeys) {
            $foreignKeys = $this->extractForeignKeys($tableName);

            // Create Additional Setters and Getters from Foreign keys
            foreach ($foreignKeys as $_foreignKey) {
                $settersAndGetters['get' . ucwords($_foreignKey->COLUMN_NAME)] =
                    $this->writeAccessors(
                        $entityStubsPath,
                        $_foreignKey->VARIABLE_NAME,
                        $_foreignKey->ENTITY_DATA_TYPE,
                        'getter'
                    );
                $settersAndGetters['set' . ucwords($_foreignKey->COLUMN_NAME)] =
                    $this->writeAccessors(
                        $entityStubsPath,
                        $_foreignKey->VARIABLE_NAME,
                        $_foreignKey->ENTITY_DATA_TYPE,
                        'setter'
                    );
            }
        }
        return $settersAndGetters;
    }

    private function writeAttribute(string $entityStubsPath, string $attributeName, string $attributeType): string
    {
        $attributeStub = file_get_contents($entityStubsPath . 'attribute.stub');
        return str_replace(['{{ AttributeType }}', '{{ AttributeName }}'],
            [$attributeType, $attributeName],
            $attributeStub);
    }

    private function writeAccessors(string $entityStubsPath, string $attributeName, string $attributeType, string $type): string
    {
        $accessorStub = file_get_contents($entityStubsPath . $type . '.stub');
        return str_replace(['{{ AttributeType }}', '{{ AttributeName }}', '{{ GetterName }}', '{{ SetterName }}'],
            [$attributeType, $attributeName, ucfirst($attributeName), ucfirst($attributeName)],
            $accessorStub);
    }

    public function getNameSpace(): string
    {
        return $this->entityNamespace;
    }

    public function getClassName(): string
    {
        return $this->entityName;
    }
}
