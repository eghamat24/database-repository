<?php

namespace Nanvaie\DatabaseRepository\Creators;

use Illuminate\Support\Collection;
use Nanvaie\DatabaseRepository\CustomMySqlQueries;
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
        return ["use $this->entityNamespace\\$this->entityName;",
            "use Nanvaie\DatabaseRepository\Models\Entity\Entity;",
            "use Nanvaie\DatabaseRepository\Models\Resources\Resource;"];
    }

    public function getClassName(): string
    {
        return $this->resourceName;
    }

    public function getExtendSection(): string
    {
        return 'extends Resource';
    }

    public function createAttributs(): array
    {
        return [];
    }

    public function createFunctions(): array
    {

        $getterStub = file_get_contents($this->resourceStubsPath . 'getter.default.stub');
        $foreignGetterStub = file_get_contents($this->resourceStubsPath . 'getter.foreign.stub');
        $foreignFunStub = file_get_contents($this->resourceStubsPath . 'function.foreign.stub');
        $getterFunStub = file_get_contents($this->resourceStubsPath . 'function.getter.stub');

        $getters = '';
        foreach ($this->columns as $_column) {
            $getters .= $this->writeGetter($getterStub, $_column->COLUMN_NAME, Str::camel($_column->COLUMN_NAME));
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

    public function writeGetter(string $getterStub, string $columnName, string $attributeName)
    {
        return str_replace(['{{ ColumnName }}', '{{ GetterName }}'],
            [$columnName, ucfirst($attributeName)],
            $getterStub);
    }

    public function writeForeignGetter(string $foreignGetterStub, string $columnName, string $attributeName)
    {
        return str_replace(['{{ AttributeName }}', '{{ GetterName }}', '{{ AttributeType }}'],
            [Str::snake($columnName), ucfirst($columnName), ucfirst($attributeName)],
            $foreignGetterStub);
    }
}
