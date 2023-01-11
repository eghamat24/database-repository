<?php

namespace Nanvaie\DatabaseRepository\Creators;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Nanvaie\DatabaseRepository\CustomMySqlQueries;

class CreatorMySqlRepository implements IClassCreator
{
    use CustomMySqlQueries;

    public function __construct(
        public Collection $columns,
        public string     $tableName,
        public string     $entityName,
        public string     $entityVariableName,
        public string     $factoryName,
        public string     $entityNamespace,
        public string     $factoryNamespace,
        public string     $mysqlRepositoryName,
        public string     $repositoryNamespace,
        public string     $interfaceName,
        public string     $mysqlRepositoryStubsPath,
        public string     $detectForeignKeys

    )
    {
    }
    public function getNameSpace(): string
    {
        return $this->repositoryNamespace . '\\' . $this->entityName;
    }
    public function createUses(): array
    {
        return [
            "use $this->entityNamespace\\$this->entityName;",
            "use $this->factoryNamespace\\$this->factoryName;",
            "use Illuminate\Support\Collection;",
            "use Nanvaie\DatabaseRepository\Models\Repositories\MySqlRepository;"
        ];
    }
    public function getClassName(): string
    {
        return $this->mysqlRepositoryName;
    }
    public function getExtendSection(): string
    {
        return "extends MySqlRepository implements " . $this->interfaceName;
    }
    public function createAttributs(): array
    {
        return [];
    }
    public function createFunctions(): array
    {

        $baseContent = file_get_contents($this->mysqlRepositoryStubsPath . 'class.stub');
        $constructContent = file_get_contents($this->mysqlRepositoryStubsPath . 'construct.stub');
        $getOneStub = file_get_contents($this->mysqlRepositoryStubsPath . 'getOneBy.stub');
        $getAllStub = file_get_contents($this->mysqlRepositoryStubsPath . 'getAllBy.stub');
        $createFunctionStub = file_get_contents($this->mysqlRepositoryStubsPath . 'create.stub');
        $updateFunctionStub = file_get_contents($this->mysqlRepositoryStubsPath . 'update.stub');
        $deleteStub = file_get_contents($this->mysqlRepositoryStubsPath . 'delete.stub');
        $undeleteStub = file_get_contents($this->mysqlRepositoryStubsPath . 'undelete.stub');
        $getterStub = file_get_contents($this->mysqlRepositoryStubsPath . 'getter.stub');
        $setterStub = file_get_contents($this->mysqlRepositoryStubsPath . 'setter.stub');
        $timeFieldStub = file_get_contents($this->mysqlRepositoryStubsPath . 'timeField.stub');

        $functions = [];
        // Initialize MySql Repository
        $hasSoftDelete = in_array('deleted_at', $this->columns->pluck('COLUMN_NAME')->toArray(), true);
        $functions['__construct'] = $this->getConstruct($this->tableName, $this->factoryName, $hasSoftDelete, $constructContent);
        $functions['getOneById'] = $this->writeGetOneFunction($getOneStub, 'id', 'int');
        $functions['getAllByIds'] = $this->writeGetAllFunction($getAllStub, 'id', 'int');

        $indexes = $this->extractIndexes($this->tableName);
        foreach ($indexes as $index) {
            $indx = 'getOneBy' . ucfirst(Str::camel($index->COLUMN_NAME));
            $functions[$indx] = $this->writeGetOneFunction($getOneStub, $index->COLUMN_NAME, $this->entityName);
            $indx = 'getAllBy' . ucfirst(Str::plural(Str::camel($index->COLUMN_NAME)));
            $functions[$indx] = $this->writeGetAllFunction($getAllStub, $index->COLUMN_NAME, $this->entityName);
        }

        if ($this->detectForeignKeys) {
            $foreignKeys = $this->extractForeignKeys($this->tableName);
            foreach ($foreignKeys as $_foreignKey) {
                $indx = 'getOneBy' . ucfirst(Str::camel($_foreignKey->COLUMN_NAME));
                $functions[$indx] = $this->writeGetOneFunction($getOneStub, $_foreignKey->COLUMN_NAME, $this->entityName);
                $indx = 'getAllBy' . ucfirst(Str::plural(Str::camel($_foreignKey->COLUMN_NAME)));
                $functions[$indx] = $this->writeGetAllFunction($getAllStub, $_foreignKey->COLUMN_NAME, $this->entityName);
            }
        }

        $getterFunctions = '';
        $setterFunctions = '';
        // Create "create" function
        foreach ($this->columns as $_column) {
            if (!in_array($_column->COLUMN_NAME, ['id', 'deleted_at'])) {
                $getterFunctions .= $this->writeGetterFunction($getterStub, $_column->COLUMN_NAME);
            }
            if (in_array($_column->COLUMN_NAME, ['created_at', 'updated_at'], true)) {
                $setterFunctions .= $this->writeSetterFunction($setterStub, $_column->COLUMN_NAME);
            }
        }
        $createFunctionStub = str_replace(["{{ GetterFunctions }}", "{{ SetterFunctions }}"],
            [substr($getterFunctions, 0, -1), substr($setterFunctions, 0, -1)],
            $createFunctionStub
        );

        $functions['create'] = $createFunctionStub;

        $getterFunctions = '';
        $setterFunctions = '';
        // Create "update" function
        foreach ($this->columns as $_column) {
            if (!in_array($_column->COLUMN_NAME, ['id', 'created_at', 'deleted_at'])) {
                $getterFunctions .= $this->writeGetterFunction($getterStub, $_column->COLUMN_NAME);
            }
            if ($_column->COLUMN_NAME === 'updated_at') {
                $setterFunctions .= $this->writeSetterFunction($setterStub, $_column->COLUMN_NAME);
            }
        }
        $updateFunctionStub = str_replace(["{{ GetterFunctions }}", "{{ UpdateFieldSetter }}"],
            [substr($getterFunctions, 0, -1), substr($setterFunctions, 0, -1)],
            $updateFunctionStub
        );

        $functions['update'] = $updateFunctionStub;

        // Create "delete" and "undelete" functions if necessary
        if ($hasSoftDelete) {
            $functions['remove'] = $deleteStub;
            $functions['restore'] = $undeleteStub;
        }
        foreach ($functions as &$func) {
            $func = str_replace(["{{ EntityName }}", "{{ EntityVariableName }}"],
                [$this->entityName, $this->entityVariableName],
                $func
            );
        }
        return $functions;
    }

    private function writeGetOneFunction(string $getOneStub, string $columnName, string $attributeType): string
    {
        return str_replace(['{{ FunctionName }}', '{{ ColumnName }}', '{{ AttributeType }}', '{{ AttributeName }}'],
            [ucfirst(Str::camel($columnName)), $columnName, $attributeType, Str::camel($columnName)],
            $getOneStub);
    }

    private function writeGetAllFunction(string $getOneStub, string $columnName, string $attributeType): string
    {
        return str_replace(['{{ FunctionNamePlural }}', '{{ ColumnName }}', '{{ AttributeType }}', '{{ AttributeNamePlural }}'],
            [ucfirst(Str::plural(Str::camel($columnName))), $columnName, $attributeType, Str::plural(Str::camel($columnName))],
            $getOneStub);
    }

    private function writeGetterFunction(string $getterStub, string $columnName): string
    {
        return str_replace(['{{ ColumnName }}', '{{ GetterName }}'],
            [$columnName, ucfirst(Str::camel($columnName))],
            $getterStub);
    }

    private function writeSetterFunction(string $setterStub, string $columnName): string
    {
        return str_replace('{{ SetterName }}',
            ucfirst(Str::camel($columnName)),
            $setterStub);
    }

    private function getConstruct(string $tableName, string $factoryName, bool $hasSoftDelete, string $constructContent)
    {
        return str_replace(
            ['{{ TableName }}', '{{ FactoryName }}', '{{ HasSoftDelete }}'],
            [$tableName, $factoryName, $hasSoftDelete ? 'true' : 'false'],
            $constructContent);
    }
}
