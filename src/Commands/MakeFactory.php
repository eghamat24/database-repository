<?php

namespace Nanvaie\DatabaseRepository\Commands;

use Nanvaie\DatabaseRepository\CustomMySqlQueries;
use Illuminate\Console\Command;

class MakeFactory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repository:make-factory {table_name}
    {--d|delete : Delete resource}
    {--f|force : Override/Delete existing factory class}
    {--g|add-to-git : Add created file to git repository}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new factory.';

    use CustomMySqlQueries;

    public function writeSetter(string $setterStub, string $columnName): string
    {
        return str_replace(['{{ SetterName }}', '{{ AttributeName }}'],
            [ucfirst($columnName), snake_case($columnName)],
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
        $entityName = str_singular(ucfirst(camel_case($tableName)));
        $entityVariableName = camel_case($entityName);
        $factoryName = $entityName.'Factory';
        $entityNamespace = config('repository.path.namespace.entities');
        $factoryNamespace = config('repository.path.namespace.factories');
        $relativeFactoriesPath = config('repository.path.relative.factories');
        $factoryStubsPath = __DIR__ . '/../../' . config('repository.path.stub.factories');
        $phpVersion = config('repository.php_version');
        $filenameWithPath = $relativeFactoriesPath.$factoryName.'.php';

        if ($this->option('delete')) {
            unlink("$relativeFactoriesPath/$factoryName.php");
            $this->info("Factory \"$factoryName\" has been deleted.");
            return 0;
        }

        if ( ! file_exists($relativeFactoriesPath) && ! mkdir($relativeFactoriesPath, 0775, true) && ! is_dir($relativeFactoriesPath)) {
            $this->alert("Directory \"$relativeFactoriesPath\" was not created");
            return 0;
        }

        if (class_exists("$relativeFactoriesPath\\$factoryName") && ! $this->option('force')) {
            $this->alert("Factory $factoryName is already exist!");
            return 0;
        }

        $columns = $this->getAllColumnsInTable($tableName);

        if ($columns->isEmpty()) {
            $this->alert("Couldn't retrieve columns from table ".$tableName."! Perhaps table's name is misspelled.");
            die;
        }

        foreach ($columns as $_column) {
            $_column->COLUMN_NAME = camel_case($_column->COLUMN_NAME);
        }

        $baseContent = file_get_contents($factoryStubsPath.'class.stub');
        $setterStub = file_get_contents($factoryStubsPath.'setter.stub');

        // Initialize Class
        $setterFunctions = '';
        foreach ($columns as $_column) {
            $setterFunctions .= $this->writeSetter($setterStub, $_column->COLUMN_NAME);
        }

        $baseContent = str_replace(['{{ SetterFunctions }}', '{{ EntityName }}', '{{ EntityNamespace }}', '{{ FactoryName }}', '{{ FactoryNamespace }}', '{{ EntityVariableName }}'],
            [$setterFunctions, $entityName, $entityNamespace, $factoryName, $factoryNamespace, $entityVariableName],
            $baseContent);

        file_put_contents($filenameWithPath, $baseContent);

        if ($this->option('add-to-git')) {
            shell_exec("git add $filenameWithPath");
        }

        $this->info("Factory \"$factoryName\" has been created.");

        return 0;
    }
}