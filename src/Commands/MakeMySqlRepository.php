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

    private function writeGetOneFunction(string $getOneStub, string $columnName, string $attributeType): string
    {
        return str_replace(['{{ FunctionName }}', '{{ ColumnName }}', '{{ AttributeType }}', '{{ AttributeName }}'],
            [ucfirst(camel_case($columnName)), $columnName, $attributeType, camel_case($columnName)],
            $getOneStub);
    }

    private function writeGetAllFunction(string $getOneStub, string $columnName, string $attributeType): string
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

    private function writeSetterFunction(string $setterStub, string $columnName): string
    {
        return str_replace('{{ SetterName }}',
            ucfirst(camel_case($columnName)),
            $setterStub);
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
        $relativeMysqlRepositoryPath = config('repository.path.relative.repositories')."$entityName";
        $mysqlRepositoryStubsPath = __DIR__ . '/../../' . config('repository.path.stub.repositories.mysql');
        $phpVersion = config('repository.php_version');
        $filenameWithPath = $relativeMysqlRepositoryPath . '/' . $mysqlRepositoryName.'.php';

        if ($this->option('delete')) {
            unlink("$relativeMysqlRepositoryPath/$mysqlRepositoryName.php");
            $this->info("MySql Repository \"$mysqlRepositoryName\" has been deleted.");
            return 0;
        }

        if ( ! file_exists($relativeMysqlRepositoryPath) && ! mkdir($relativeMysqlRepositoryPath, 0775, true) && ! is_dir($relativeMysqlRepositoryPath)) {
            $this->alert("Directory \"$relativeMysqlRepositoryPath\" was not created");
            return 0;
        }

        if (class_exists("$relativeMysqlRepositoryPath\\$mysqlRepositoryName") && ! $this->option('force')) {
            $this->alert("Repository $mysqlRepositoryName is already exist!");
            return 0;
        }

        $columns = $this->getAllColumnsInTable($tableName);

        if ($columns->isEmpty()) {
            $this->alert("Couldn't retrieve columns from table ".$tableName."! Perhaps table's name is misspelled.");
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
        $setterStub = file_get_contents($mysqlRepositoryStubsPath.'setter.stub');
        $timeFieldStub = file_get_contents($mysqlRepositoryStubsPath.'timeField.stub');
        $functions = '';

        // Initialize MySql Repository
        $functions .= $this->writeGetOneFunction($getOneStub, 'id', 'int');
        $functions .= $this->writeGetAllFunction($getAllStub, 'id', 'int');

        if ($detectForeignKeys) {
            foreach ($foreignKeys as $_foreignKey) {
                $functions .= $this->writeGetOneFunction($getOneStub, $_foreignKey->COLUMN_NAME, $entityName);
                $functions .= $this->writeGetAllFunction($getAllStub, $_foreignKey->COLUMN_NAME, $entityName);
            }
        }

        $getterFunctions = '';
        $setterFunctions = '';
        // Create "create" function
        foreach ($columns as $_column) {
            if ( ! in_array($_column->COLUMN_NAME, ['id', 'deleted_at'])) {
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

        $functions .= $createFunctionStub;

        $getterFunctions = '';
        $setterFunctions = '';
        // Create "update" function
        foreach ($columns as $_column) {
            if ( ! in_array($_column->COLUMN_NAME, ['id', 'created_at', 'deleted_at'])) {
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

        $functions .= $updateFunctionStub;

        // Create "delete" and "undelete" functions if necessary
        $hasSoftDelete = in_array('deleted_at', $columns->pluck('COLUMN_NAME')->toArray(), true);
        if ($hasSoftDelete) {
            $functions .= $deleteAndUndeleteStub;
        }

        $baseContent = str_replace('{{ Functions }}',
            $functions, $baseContent);

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
