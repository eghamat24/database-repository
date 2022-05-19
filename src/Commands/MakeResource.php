<?php

namespace Nanvaie\DatabaseRepository\Commands;

use Illuminate\Console\Command;
use Nanvaie\DatabaseRepository\CustomMySqlQueries;

class MakeResource extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repository:make-resource {table_name}
    {--k|foreign-keys : Detect foreign keys}
    {--d|delete : Delete resource}
    {--f|force : Override/Delete existing mysql repository}
    {--g|add-to-git : Add created file to git repository}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create new resource';

    use CustomMySqlQueries;

    public function writeGetter(string $getterStub, string $columnName, string $attributeName)
    {
        return str_replace(['{{ ColumnName }}', '{{ GetterName }}'],
            [$columnName, ucfirst($attributeName)],
            $getterStub);
    }

    public function writeForeignGetter(string $foreignGetterStub, string $columnName, string $attributeName)
    {
        return str_replace(['{{ AttributeName }}', '{{ GetterName }}', '{{ AttributeType }}'],
            [$columnName, ucfirst($attributeName), ucfirst($attributeName)],
            $foreignGetterStub);
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
        $entityNamespace = config('repository.path.namespace.entities');
        $resourceName = $entityName . "Resource";
        $resourceNamespace = config('repository.path.namespace.resources');
        $relativeResourcesPath = config('repository.path.relative.resources');
        $resourceStubsPath = config('repository.path.stub.resources');
        $filenameWithPath = $relativeResourcesPath.$resourceName.'.php';

        if ($this->option('delete')) {
            unlink("$relativeResourcesPath/$resourceName.php");
            $this->info("Resource \"$resourceName\" has been deleted.");
            return 0;
        }

        if ( ! file_exists($relativeResourcesPath) && ! mkdir($relativeResourcesPath, 775, true) && ! is_dir($relativeResourcesPath)) {
            $this->alert("Directory \"$relativeResourcesPath\" was not created");
            return 0;
        }

        if (class_exists("$relativeResourcesPath\\$resourceName") && !$this->option('force')) {
            $this->alert("Resource $resourceName is already exist!");
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

        $baseContent = file_get_contents($resourceStubsPath.'class.stub');
        $getterStub = file_get_contents($resourceStubsPath.'getter.default.stub');
        $foreignGetterStub = file_get_contents($resourceStubsPath.'getter.foreign.stub');

        foreach ($columns as $_column) {
            $baseContent = substr_replace($baseContent,
                $this->writeGetter($getterStub, $_column->COLUMN_NAME, camel_case($_column->COLUMN_NAME)),
                -293, 0);
        }

        if ($detectForeignKeys) {
            foreach ($foreignKeys as $_foreignKey) {
                $baseContent = substr_replace($baseContent,
                    $this->writeForeignGetter($foreignGetterStub, $_foreignKey->VARIABLE_NAME, $_foreignKey->ENTITY_DATA_TYPE),
                    -19, 0);
            }
        }

        $baseContent = str_replace(['{{ EntityName }}', '{{ EntityNamespace }}', '{{ EntityVariableName }}', '{{ ResourceName }}', '{{ ResourceNamespace }}'],
            [$entityName, $entityNamespace, $entityVariableName, $resourceName, $resourceNamespace],
            $baseContent);

        file_put_contents($filenameWithPath, $baseContent);

        if ($this->option('add-to-git')) {
            shell_exec("git add $filenameWithPath");
        }

        $this->info("Resource \"$resourceName\" has been created.");

        return 0;
    }
}
