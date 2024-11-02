<?php

namespace Eghamat24\DatabaseRepository\Creators;

use Illuminate\Support\Collection;
use Eghamat24\DatabaseRepository\CustomMySqlQueries;
use Illuminate\Support\Str;

class CreatorResource implements IClassCreator
{
    use CustomMySqlQueries;

    public function __construct(
        public Collection $columns,
        public string     $tableName,
        public string     $entityName,
        public string     $entityNamespace,
        public string     $resourceNamespace,
        public string     $resourceName,
        public string     $resourceStubsPath,
        public string     $detectForeignKeys,
        public string     $entityVariableName
    )
    {
    }

    public function getNameSpace(): string
    {
        return $this->resourceNamespace;
    }

    public function createUses(): array
    {
        return [
            "use $this->entityNamespace\\$this->entityName;",
            'use Eghamat24\DatabaseRepository\Models\Entity\Entity;',
            'use Eghamat24\DatabaseRepository\Models\Resources\Resource;'
        ];
    }

    public function getClassName(): string
    {
        return $this->resourceName;
    }

    public function getExtendSection(): string
    {
        return 'extends Resource';
    }

    public function createAttributes(): array
    {
        return [];
    }

    public function createFunctions(): array
    {
        $getsStub = file_get_contents($this->resourceStubsPath . 'getter.default.stub');
        $foreignGetterStub = file_get_contents($this->resourceStubsPath . 'getter.foreign.stub');
        $foreignFunStub = file_get_contents($this->resourceStubsPath . 'function.foreign.stub');
        $getterFunStub = file_get_contents($this->resourceStubsPath . 'function.getter.stub');

        $getters = '';
        foreach ($this->columns as $_column) {
            $getters .= $this->writeGet($getsStub, $_column->COLUMN_NAME, Str::camel($_column->COLUMN_NAME)) . "\t\t\t";
        }

        $foreignGetterFunctions = '';
        if ($this->detectForeignKeys) {
            $foreignKeys = $this->extractForeignKeys($this->tableName);
            foreach ($foreignKeys as $_foreignKey) {
                $foreignGetterFunctions .= $this->writeForeignGetter($foreignGetterStub, $_foreignKey->VARIABLE_NAME, $_foreignKey->ENTITY_DATA_TYPE);
            }
        }

        $functions = [];
        $functions['toArray'] = str_replace(['{{ Getters }}'], [$getters], $getterFunStub);
        $functions['toArray'] = str_replace(['{{ EntityVariableName }}'], [$this->entityVariableName], $functions['toArray']);
        $functions['toArrayWithForeignKeys'] = str_replace(['{{ ForeignGetterFunctions }}'], [$foreignGetterFunctions], $foreignFunStub);
        $functions['toArrayWithForeignKeys'] = str_replace(['{{ EntityVariableName }}'], [$this->entityVariableName], $functions['toArrayWithForeignKeys']);

        return $functions;
    }

    public function writeGet(string $getterStub, string $columnName, string $attributeName): array|string
    {
        $replaceMapping = [
            '{{ ColumnName }}' => $columnName,
            '{{ AttributeName }}' => Str::camel($attributeName),
        ];

        return str_replace(array_keys($replaceMapping), array_values($replaceMapping), $getterStub);
    }

    public function writeForeignGetter(string $foreignGetterStub, string $columnName, string $attributeName): array|string
    {
        $replaceMapping = [
            '{{ AttributeName }}' => Str::snake($columnName),
            '{{ GetterName }}' => ucfirst($columnName),
            '{{ AttributeType }}' => ucfirst($attributeName)
        ];

        return str_replace(array_keys($replaceMapping), array_values($replaceMapping), $foreignGetterStub);
    }
}
