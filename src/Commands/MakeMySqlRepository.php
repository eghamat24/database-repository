<?php

namespace Nanvaie\DatabaseRepository\Commands;

use Nanvaie\DatabaseRepository\CustomMySqlQueries;
use Illuminate\Console\Command;

class MakeMySqlRepository extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repository:make-mysql-repository {table_name}
    {--k|foreign-keys : Detect foreign keys}
    {--d|delete : Delete resource}
    {--f|force : Override/Delete existing mysql repository}
    {--g|add-to-git : Add created file to git repository}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new MySql repository class';

    use CustomMySqlQueries;

    private function writeGetOneFunction(string $getOneStub, string $columnName,  string $attributeType): string
    {
        return str_replace(['{{ FunctionName }}', '{{ ColumnName }}', '{{ AttributeType }}', '{{ AttributeName }}'],
            [ucfirst(camel_case($columnName)), $columnName, $attributeType, camel_case($columnName)],
            $getOneStub);
    }

    private function writeGetAllFunction(string $getOneStub, string $columnName,  string $attributeType): string
    {
        return str_replace(['{{ FunctionNamePlural }}', '{{ ColumnName }}', '{{ AttributeType }}', '{{ AttributeNamePlural }}'],
            [ucfirst(str_plural(camel_case($columnName))), $columnName, $attributeType, str_plural(camel_case($columnName))],
            $getOneStub);
    }

    private function writeGetterFunction(string $getterStub, string $columnName): string
    {
        return str_replace(['{{ ColumnName }}', '{{ GetterName }}'],
            [$columnName, ucfirst(camel_case($columnName))],
            $getterStub);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $tableName = $this->argument('table_name');
        $detectForeignKeys = $this->option('foreign-keys');
        $entityName = str_singular(ucfirst(camel_case($tableName)));
        $entityVariableName = camel_case($entityName);
        $factoryName = $entityName.'Factory';
        $interfaceName = 'I'.$entityName.'Repository';
        $mysqlRepositoryName = 'MySql'.$entityName.'Repository';
        $entityNamespace = config('repository.path.namespace.entities');
        $factoryNamespace = config('repository.path.namespace.factories');
        $repositoryNamespace = config('repository.path.namespace.repositories');
        $relativeMysqlRepositoryPath = config('repository.path.relative.repositories') . "\\$entityName";
        $mysqlRepositoryStubsPath = config('repository.path.stub.repositories.mysql');
        $filenameWithPath = $relativeMysqlRepositoryPath.'\\'.$mysqlRepositoryName.'.php';

        if ($this->option('delete')) {
            unlink("$relativeMysqlRepositoryPath/$mysqlRepositoryName.php");
            $this->info("MySql Repository \"$mysqlRepositoryName\" has been deleted.");
            return 0;
        }

        if ( ! file_exists($relativeMysqlRepositoryPath) && ! mkdir($relativeMysqlRepositoryPath) && ! is_dir($relativeMysqlRepositoryPath)) {
            $this->alert("Directory \"$relativeMysqlRepositoryPath\" was not created");
            return 0;
        }

        if (class_exists("$relativeMysqlRepositoryPath\\$mysqlRepositoryName") && !$this->option('force')) {
            $this->alert("Repository $mysqlRepositoryName is already exist!");
            return 0;
        }

        $columns = $this->getAllColumnsInTable($tableName);

        if ($columns->isEmpty()) {
            $this->alert("Couldn't retrieve columns from table " . $tableName . "! Perhaps table's name is misspelled.");
            die;
        }

        if ($detectForeignKeys) {
            $foreignKeys = $this->extractForeignKeys($tableName);
        }

        $baseContent = file_get_contents($mysqlRepositoryStubsPath.'class.stub');
        $getOneStub = file_get_contents($mysqlRepositoryStubsPath.'getOneBy.stub');
        $getAllStub = file_get_contents($mysqlRepositoryStubsPath.'getAllBy.stub');
        $createFunctionStub = file_get_contents($mysqlRepositoryStubsPath.'create.stub');
        $updateFunctionStub = file_get_contents($mysqlRepositoryStubsPath.'update.stub');
        $deleteAndUndeleteStub = file_get_contents($mysqlRepositoryStubsPath.'deleteAndUndelete.stub');
        $getterStub = file_get_contents($mysqlRepositoryStubsPath.'getter.stub');
        $timeFieldStub = file_get_contents($mysqlRepositoryStubsPath.'timeField.stub');

        // Initialize MySql Repository
        $baseContent = substr_replace($baseContent,
            $this->writeGetOneFunction($getOneStub, 'id', 'int'),
            -1, 0);
        $baseContent = substr_replace($baseContent,
            $this->writeGetAllFunction($getAllStub, 'id', 'int'),
            -1, 0);

        if ($detectForeignKeys) {
            foreach ($foreignKeys as $_foreignKey) {
                $baseContent = substr_replace($baseContent,
                    $this->writeGetOneFunction($getOneStub, $_foreignKey->COLUMN_NAME, $entityName),
                    -1, 0);
                $baseContent = substr_replace($baseContent,
                    $this->writeGetAllFunction($getAllStub, $_foreignKey->COLUMN_NAME, $entityName),
                    -1, 0);
            }
        }

        // Create "create" function
        foreach ($columns as $_column) {
            if ( ! in_array($_column->COLUMN_NAME, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                $createFunctionStub = substr_replace($createFunctionStub,
                    $this->writeGetterFunction($getterStub, $_column->COLUMN_NAME),
                    -95, 0);
            } elseif ($_column->COLUMN_NAME === 'created_at') {
                $createFunctionStub = substr_replace($createFunctionStub,
                    $this->writeGetterFunction($timeFieldStub, $_column->COLUMN_NAME),
                    -95, 0);
            }
        }
        $baseContent = substr_replace($baseContent, $createFunctionStub, -1, 0);

        // Create "update" function
        foreach ($columns as $_column) {
            if ( ! in_array($_column->COLUMN_NAME, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                $updateFunctionStub = substr_replace($updateFunctionStub,
                    $this->writeGetterFunction($getterStub, $_column->COLUMN_NAME),
                    -12, 0);
            } elseif ($_column->COLUMN_NAME === 'updated_at') {
                $updateFunctionStub = substr_replace($updateFunctionStub,
                    $this->writeGetterFunction($getterStub, $_column->COLUMN_NAME),
                    -12, 0);
            }
        }
        $baseContent = substr_replace($baseContent, $updateFunctionStub, -1, 0);

        // Create "delete" and "undelete" functions if necessary
        $hasSoftDelete = in_array('deleted_at', $columns->pluck('COLUMN_NAME')->toArray(), true);
        if ($hasSoftDelete) {
            $baseContent = substr_replace($baseContent,$deleteAndUndeleteStub, -1, 0);
        }

        $baseContent = str_replace(['{{ EntityName }}', '{{ EntityNamespace }}', '{{ FactoryName }}', '{{ FactoryNamespace }}', '{{ EntityVariableName }}', '{{ MySqlRepositoryName }}', '{{ RepositoryNamespace }}', '{{ RepositoryInterfaceName }}', '{{ TableName }}', '{{ HasSoftDelete }}'],
            [$entityName, $entityNamespace, $factoryName, $factoryNamespace, $entityVariableName, $mysqlRepositoryName, $repositoryNamespace, $interfaceName, $tableName, $hasSoftDelete ? 'true' : 'false'],
            $baseContent);

        file_put_contents($filenameWithPath, $baseContent);

        if ($this->option('add-to-git')) {
            shell_exec("git add $filenameWithPath");
        }

        $this->info("MySql Repository \"$mysqlRepositoryName\" has been created.");

        return 0;
    }
}