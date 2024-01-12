<?php

namespace Eghamat24\DatabaseRepository\Creators;

use Eghamat24\DatabaseRepository\Models\Enums\DataTypeEnum;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Eghamat24\DatabaseRepository\CustomMySqlQueries;

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
            'use Illuminate\Support\Collection;',
            'use Eghamat24\DatabaseRepository\Models\Repositories\MySqlRepository;'
        ];
    }

    public function getClassName(): string
    {
        return $this->mysqlRepositoryName;
    }

    public function getExtendSection(): string
    {
        return 'extends MySqlRepository implements ' . $this->interfaceName;
    }

    public function createAttributes(): array
    {
        return [];
    }

    public function createFunctions(): array
    {

        $stubList = [
            'baseContent' => 'class.stub',
            'constructContent' => 'construct.stub',
            'getOneStub' => 'getOneBy.stub',
            'getAllStub' => 'getAllBy.stub',
            'createFunctionStub' => 'create.stub',
            'updateFunctionStub' => 'update.stub',
            'deleteStub' => 'delete.stub',
            'undeleteStub' => 'undelete.stub',
            'getterStub' => 'getter.stub',
            'setterStub' => 'setter.stub',
            'timeFieldStub' => 'timeField.stub',
        ];

        $stubContent = [];
        foreach ($stubList as $stubKey => $stubName) {
            $stubContent[$stubKey] = file_get_contents($this->mysqlRepositoryStubsPath . $stubName);
        }

        $hasSoftDelete = in_array('deleted_at', $this->columns->pluck('COLUMN_NAME')->toArray(), true);

        $functions = [];
        $functions['__construct'] = $this->getConstruct($this->tableName, $this->factoryName, $hasSoftDelete, $stubContent['constructContent']);
        $functions['getOneById'] = $this->writeGetOneFunction($stubContent['getOneStub'], 'id', DataTypeEnum::INTEGER_TYPE);
        $functions['getAllByIds'] = $this->writeGetAllFunction($stubContent['getAllStub'], 'id', DataTypeEnum::INTEGER_TYPE);
        $columnsInfo = $this->getAllColumnsInTable($this->tableName);

        $indexes = $this->extractIndexes($this->tableName);
        foreach ($indexes as $index) {
            $columnInfo = collect($columnsInfo)->where('COLUMN_NAME', $index->COLUMN_NAME)->first();
            $indx = 'getOneBy' . ucfirst(Str::camel($index->COLUMN_NAME));
            $functions[$indx] = $this->writeGetOneFunction(
                $stubContent['getOneStub'],
                $index->COLUMN_NAME,
                $this->getDataType($columnInfo->COLUMN_TYPE, $columnInfo->DATA_TYPE)
            );

            if ($index->Non_unique == 1) {
                $indx = 'getAllBy' . ucfirst(Str::plural(Str::camel($index->COLUMN_NAME)));
                $functions[$indx] = $this->writeGetAllFunction($stubContent['getAllStub'], $index->COLUMN_NAME, $this->entityName);
            }
        }

        if ($this->detectForeignKeys) {
            $foreignKeys = $this->extractForeignKeys($this->tableName);
            foreach ($foreignKeys as $_foreignKey) {
                $indx = 'getOneBy' . ucfirst(Str::camel($_foreignKey->COLUMN_NAME));
                $functions[$indx] = $this->writeGetOneFunction($stubContent['getOneStub'], $_foreignKey->COLUMN_NAME, $this->entityName);
                $indx = 'getAllBy' . ucfirst(Str::plural(Str::camel($_foreignKey->COLUMN_NAME)));
                $functions[$indx] = $this->writeGetAllFunction($stubContent['getAllStub'], $_foreignKey->COLUMN_NAME, $this->entityName);
            }
        }

        $getterFunctions = '';
        $setterFunctions = '';
        $functions = $this->makeCreateFunction($stubContent, $getterFunctions, $setterFunctions, $functions);

        $getterFunctions = '';
        $setterFunctions = '';
        $functions = $this->makeUpdateFunction($stubContent, $getterFunctions, $setterFunctions, $functions);

        // Create "delete" and "undelete" functions if necessary
        if ($hasSoftDelete) {
            $functions['remove'] = $stubContent['deleteStub'];
            $functions['restore'] = $stubContent['undeleteStub'];
        }

        foreach ($functions as &$func) {
            $func = str_replace(['{{ EntityName }}', '{{ EntityVariableName }}'],
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

    /**
     * @param array $stubContent
     * @param string $getterFunctions
     * @param string $setterFunctions
     * @param array $functions
     * @return array
     */
    public function makeCreateFunction(array &$stubContent, string &$getterFunctions, string &$setterFunctions, array &$functions): array
    {
        foreach ($this->columns as $_column) {
            if (!in_array($_column->COLUMN_NAME, ['id', 'deleted_at'])) {
                $getterFunctions .= trim($this->writeGetterFunction($stubContent['getterStub'], $_column->COLUMN_NAME)) . "\n\t\t\t\t";
            }

            if (in_array($_column->COLUMN_NAME, ['created_at', 'updated_at'], true)) {
                $setterFunctions .= trim($this->writeSetterFunction($stubContent['setterStub'], $_column->COLUMN_NAME)) . "\n\t\t";
            }
        }

        $createFunctionStub = str_replace(["{{ GetterFunctions }}", "{{ SetterFunctions }}"],
            [trim(substr($getterFunctions, 0, -1)), trim(substr($setterFunctions, 0, -1))],
            $stubContent['createFunctionStub']
        );

        $functions['create'] = $createFunctionStub;

        return $functions;
    }

    /**
     * @param array $stubContent
     * @param string $getterFunctions
     * @param string $setterFunctions
     * @param array $functions
     * @return array
     */
    public function makeUpdateFunction(array &$stubContent, string &$getterFunctions, string &$setterFunctions, array &$functions): array
    {
        foreach ($this->columns as $_column) {

            if (!in_array($_column->COLUMN_NAME, ['id', 'created_at', 'deleted_at'])) {
                $getterFunctions .= trim($this->writeGetterFunction($stubContent['getterStub'], $_column->COLUMN_NAME)) . "\n\t\t\t\t";
            }

            if ($_column->COLUMN_NAME === 'updated_at') {
                $setterFunctions .= trim($this->writeSetterFunction($stubContent['setterStub'], $_column->COLUMN_NAME)) . "\n\t\t";
            }
        }

        $updateFunctionStub = str_replace(['{{ GetterFunctions }}', '{{ UpdateFieldSetter }}'],
            [trim(substr($getterFunctions, 0, -1)), trim(substr($setterFunctions, 0, -1))],
            $stubContent['updateFunctionStub']
        );

        $functions['update'] = $updateFunctionStub;

        return $functions;
    }
}
