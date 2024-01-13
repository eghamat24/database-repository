<?php

namespace Eghamat24\DatabaseRepository\Creators;

use Eghamat24\DatabaseRepository\Models\Enums\DataTypeEnum;
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
        $attributes = [];

        foreach ($this->columns as $_column) {

            $dataType = $this->getDataType($_column->COLUMN_TYPE, $_column->DATA_TYPE);

            $defaultValue = null;
            if ($_column->COLUMN_DEFAULT !== null) {
                $defaultValue = $_column->COLUMN_DEFAULT;

                if ($dataType === DataTypeEnum::INTEGER_TYPE) {
                    $defaultValue = intval($defaultValue);
                }

                if ($dataType === self::BOOL_TYPE) {
                    if (in_array($defaultValue, [0, '', "''"])) {
                        $defaultValue = 'false';
                    } elseif (in_array($defaultValue, [1, '1'])) {
                        $defaultValue = 'true';
                    }
                }
            }

            $columnString = $_column->COLUMN_NAME;

            if (!in_array($_column->COLUMN_DEFAULT, [null, 'NULL'])) {
                $columnString .= ' = ' . $defaultValue;
            }

            if ($_column->IS_NULLABLE === 'YES') {
                $columnString .= ' = null';
            }

            $attributes[$_column->COLUMN_NAME] =
                $this->writeAttribute(
                    $this->entityStubsPath,
                    $columnString,
                    ($_column->IS_NULLABLE === 'YES' ? 'null|' : '') . $dataType
                );
        }

        if ($this->detectForeignKeys) {
            $foreignKeys = $this->extractForeignKeys($this->tableName);

            // Create Additional Attributes from Foreign Keys
            foreach ($foreignKeys as $_foreignKey) {
                $attributes[$_column->COLUMN_NAME] =
                    $this->writeAttribute(
                        $this->entityStubsPath,
                        $_foreignKey->VARIABLE_NAME,
                        $_foreignKey->ENTITY_DATA_TYPE
                    );
            }
        }

        return $attributes;
    }

    public function createUses(): array
    {
        return ['use Eghamat24\DatabaseRepository\Models\Entity\Entity;'];
    }

    public function createFunctions(): array
    {
        $settersAndGetters = [];

        foreach ($this->columns as $_column) {
            $dataType = $this->getDataType($_column->COLUMN_TYPE, $_column->DATA_TYPE);

            $settersAndGetters['get' . ucwords($_column->COLUMN_NAME)] =
                $this->writeAccessors(
                    $this->entityStubsPath,
                    $_column->COLUMN_NAME,
                    ($_column->IS_NULLABLE === 'YES' ? 'null|' : '') . $dataType,
                    'getter'
                );

            $settersAndGetters['set' . ucwords($_column->COLUMN_NAME)] =
                $this->writeAccessors(
                    $this->entityStubsPath,
                    $_column->COLUMN_NAME,
                    ($_column->IS_NULLABLE === 'YES' ? 'null|' : '') . $dataType,
                    'setter'
                );

        }

        if ($this->detectForeignKeys) {
            $foreignKeys = $this->extractForeignKeys($this->tableName);

            // Create Additional Setters and Getters from Foreign keys
            foreach ($foreignKeys as $_foreignKey) {

                $settersAndGetters['get' . ucwords($_foreignKey->COLUMN_NAME)] =
                    $this->writeAccessors(
                        $this->entityStubsPath,
                        $_foreignKey->VARIABLE_NAME,
                        $_foreignKey->ENTITY_DATA_TYPE,
                        'getter'
                    );

                $settersAndGetters['set' . ucwords($_foreignKey->COLUMN_NAME)] =
                    $this->writeAccessors(
                        $this->entityStubsPath,
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

        $replaceMapping = [
            '{{ AttributeType }}' => $attributeType,
            '{{ AttributeName }}' => $attributeName,
        ];

        return str_replace(array_keys($replaceMapping), array_values($replaceMapping), $attributeStub);
    }

    private function writeAccessors(string $entityStubsPath, string $attributeName, string $attributeType, string $type): string
    {
        $accessorStub = file_get_contents($entityStubsPath . $type . '.stub');

        $replaceMapping = [
            '{{ AttributeType }}' => $attributeType,
            '{{ AttributeName }}' => $attributeName,
            '{{ GetterName }}' => ucfirst($attributeName),
            '{{ SetterName }}' => ucfirst($attributeName)
        ];

        return str_replace(array_keys($replaceMapping), array_values($replaceMapping), $accessorStub);
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
