<?php

namespace Eghamat24\DatabaseRepository\Commands;

use Illuminate\Support\Str;
use Eghamat24\DatabaseRepository\CustomMySqlQueries;
use Illuminate\Console\Command;

class MakeInterfaceRepository extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repository:make-interface-repository {table_name}
    {--k|foreign-keys : Detect foreign keys}
    {--d|delete : Delete resource}
    {--f|force : Override/Delete existing mysql repository}
    {--g|add-to-git : Add created file to git repository}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new interface for repository';

    use CustomMySqlQueries;

    private function writeGetOneFunction(string $getOneStub, string $columnName, string $attributeType): string
    {
        return str_replace(['{{ FunctionName }}', '{{ ColumnName }}', '{{ AttributeType }}', '{{ AttributeName }}'],
            [ucfirst(Str::camel($columnName)), $columnName, $attributeType, Str::camel($columnName)],
            $getOneStub);
    }

    private function writeGetAllFunction(string $getOneStub, string $columnName, string $attributeType): string
    {
        return str_replace(['{{ FunctionNamePlural }}', '{{ AttributeType }}', '{{ AttributeNamePlural }}'],
            [ucfirst(Str::plural(Str::camel($columnName))), $attributeType, Str::plural(Str::camel($columnName))],
            $getOneStub);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): void
    {
        $this->setArguments();
        $filenameWithPath = $this->relativeInterfacePath . $this->interfaceName . '.php';

        $this->checkDelete($filenameWithPath, $this->interfaceName, "Interface");
        $this->checkDirectory($this->relativeInterfacePath);
        $this->checkClassExist($this->repositoryNamespace, $this->interfaceName, "Interface");

        $columns = $this->getAllColumnsInTable($this->tableName);
        $this->checkEmpty($columns, $this->tableName);

        if ($this->detectForeignKeys) {
            $foreignKeys = $this->extractForeignKeys($this->tableName);
        }

        $baseContent = file_get_contents($this->interfaceRepositoryStubsPath . 'class.stub');
        $getOneStub = file_get_contents($this->interfaceRepositoryStubsPath . 'getOneBy.stub');
        $getAllStub = file_get_contents($this->interfaceRepositoryStubsPath . 'getAllBy.stub');
        $createFunctionStub = file_get_contents($this->interfaceRepositoryStubsPath . 'create.stub');
        $updateFunctionStub = file_get_contents($this->interfaceRepositoryStubsPath . 'update.stub');
        $deleteAndUndeleteStub = file_get_contents($this->interfaceRepositoryStubsPath . 'deleteAndUndelete.stub');

        $baseContent = substr_replace($baseContent, $this->writeGetOneFunction($getOneStub, 'id', 'int'), -2, 0);
        $baseContent = substr_replace($baseContent, $this->writeGetAllFunction($getAllStub, 'id', 'int'), -2, 0);
        $columnsInfo = $this->getAllColumnsInTable($this->tableName);

        $indexes = $this->extractIndexes($this->tableName);
        foreach ($indexes as $index) {
            $columnInfo = collect($columnsInfo)->where('COLUMN_NAME', $index->COLUMN_NAME)->first();
            $baseContent = substr_replace($baseContent, $this->writeGetOneFunction($getOneStub, $index->COLUMN_NAME, $this->getDataType($columnInfo->COLUMN_TYPE, $columnInfo->DATA_TYPE)), -2, 0);

            if($index->Non_unique == 1) {
                $baseContent = substr_replace($baseContent, $this->writeGetOneFunction($getAllStub, $index->COLUMN_NAME, $this->getDataType($columnInfo->COLUMN_TYPE, $columnInfo->DATA_TYPE)), -2, 0);
            }
        }

        if ($this->detectForeignKeys) {
            foreach ($foreignKeys as $_foreignKey) {
                $baseContent = substr_replace($baseContent, $this->writeGetOneFunction($getOneStub, $_foreignKey->COLUMN_NAME, $this->entityName), -2, 0);
                $baseContent = substr_replace($baseContent, $this->writeGetAllFunction($getAllStub, $_foreignKey->COLUMN_NAME, $this->entityName), -2, 0);
            }
        }

        $allColumns = $columns->pluck('COLUMN_NAME')->toArray();

        if (in_array('created_at', $allColumns, true)) {
            $baseContent = substr_replace($baseContent, $createFunctionStub, -2, 0);
        }

        if (in_array('updated_at', $allColumns, true)) {
            $baseContent = substr_replace($baseContent, $updateFunctionStub, -2, 0);
        }

        if (in_array('deleted_at', $allColumns, true)) {
            $baseContent = substr_replace($baseContent, $deleteAndUndeleteStub, -2, 0);
        }

        $baseContent = str_replace(['{{ EntityName }}', '{{ EntityNamespace }}', '{{ EntityVariableName }}', '{{ InterfaceRepositoryName }}', '{{ RepositoryNamespace }}'],
            [$this->entityName, $this->entityNamespace, $this->entityVariableName, $this->interfaceName, $this->repositoryNamespace],
            $baseContent);

        $this->finalized($filenameWithPath, $this->entityName, $baseContent);
    }
}
