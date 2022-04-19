<?php

namespace Nanvaie\DatabaseRepository\Commands;

use Nanvaie\DatabaseRepository\CustomMySqlQueries;
use Illuminate\Console\Command;

class MakeInterfaceRepository extends Command
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

    private function writeGetOneFunction(string $getOneStub, string $columnName,  string $attributeType): string
    {
        return str_replace(['{{ FunctionName }}', '{{ ColumnName }}', '{{ AttributeType }}', '{{ AttributeName }}'],
            [ucfirst(camel_case($columnName)), $columnName, $attributeType, camel_case($columnName)],
            $getOneStub);
    }

    private function writeGetAllFunction(string $getOneStub, string $columnName,  string $attributeType): string
    {
        return str_replace(['{{ FunctionNamePlural }}', '{{ AttributeType }}', '{{ AttributeNamePlural }}'],
            [ucfirst(str_plural(camel_case($columnName))), $attributeType, str_plural(camel_case($columnName))],
            $getOneStub);
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
        $interfaceName = "I$entityName" . "Repository";
        $entityNamespace = config('repository.path.namespace.entities');
        $repositoryNamespace = config('repository.path.namespace.repositories');
        $relativeInterfacePath = config('repository.path.relative.repositories') . "\\$entityName";
        $interfaceRepositoryStubsPath = config('repository.path.stub.repositories.interface');
        $filenameWithPath = $relativeInterfacePath.'\\'.$interfaceName.'.php';

        if ($this->option('delete')) {
            unlink("$relativeInterfacePath/$interfaceName.php");
            $this->info("Interface \"$interfaceName\" has been deleted.");
            return 0;
        }

        if ( ! file_exists($relativeInterfacePath) && ! mkdir($relativeInterfacePath, 775, true) && ! is_dir($relativeInterfacePath)) {
            $this->alert("Directory \"$relativeInterfacePath\" was not created");
            return 0;
        }

        if (class_exists("$relativeInterfacePath\\$interfaceName") && !$this->option('force')) {
            $this->alert("Interface $interfaceName is already exist!");
            die;
        }

        $columns = $this->getAllColumnsInTable($tableName);

        if ($columns->isEmpty()) {
            $this->alert("Couldn't retrieve columns from table " . $tableName . "! Perhaps table's name is misspelled.");
            die;
        }

        if ($detectForeignKeys) {
            $foreignKeys = $this->extractForeignKeys($tableName);
        }

        $baseContent = file_get_contents($interfaceRepositoryStubsPath.'class.stub');
        $getOneStub = file_get_contents($interfaceRepositoryStubsPath.'getOneBy.stub');
        $getAllStub = file_get_contents($interfaceRepositoryStubsPath.'getAllBy.stub');
        $createFunctionStub = file_get_contents($interfaceRepositoryStubsPath.'create.stub');
        $updateFunctionStub = file_get_contents($interfaceRepositoryStubsPath.'update.stub');
        $deleteAndUndeleteStub = file_get_contents($interfaceRepositoryStubsPath.'deleteAndUndelete.stub');

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

        $allColumns = $columns->pluck('COLUMN_NAME')->toArray();

        if (in_array('created_at', $allColumns, true)) {
            $baseContent = substr_replace($baseContent, $createFunctionStub, -1, 0);
        }

        if (in_array('updated_at', $allColumns, true)) {
            $baseContent = substr_replace($baseContent, $updateFunctionStub, -1, 0);
        }

        if (in_array('deleted_at', $allColumns, true)) {
            $baseContent = substr_replace($baseContent, $deleteAndUndeleteStub, -1, 0);
        }

        $baseContent = str_replace(['{{ EntityName }}', '{{ EntityNamespace }}', '{{ EntityVariableName }}', '{{ InterfaceRepositoryName }}', '{{ RepositoryNamespace }}'],
            [$entityName, $entityNamespace, $entityVariableName, $interfaceName, $repositoryNamespace],
            $baseContent);

        file_put_contents($filenameWithPath, $baseContent);

        if ($this->option('add-to-git')) {
            shell_exec("git add $filenameWithPath");
        }

        $this->info("Interface \"$interfaceName\" has been created.");

        return 0;
    }
}
