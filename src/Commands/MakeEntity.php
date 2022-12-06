<?php

namespace Nanvaie\DatabaseRepository\Commands;

use Nanvaie\DatabaseRepository\CustomMySqlQueries;
use Illuminate\Console\Command;

class MakeEntity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repository:make-entity {table_name}
    {--k|foreign-keys : Detect foreign keys}
    {--d|delete : Delete resource}
    {--f|force : Override/Delete existing mysql repository}
    {--g|add-to-git : Add created file to git repository}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new entity.';

    use CustomMySqlQueries;

    /**
     * @param string $attributeStub
     * @param string $attributeName
     * @param string $attributeType
     * @return string
     */
    private function writeAttribute(string $attributeStub, string $attributeName, string $attributeType): string
    {
        return str_replace(['{{ AttributeType }}', '{{ AttributeName }}'],
            [$attributeType, $attributeName],
            $attributeStub);
    }

    /**
     * Generate getter and setter for given attribute.
     * @param string $accessorStub
     * @param string $attributeName
     * @param string $attributeType
     * @return string
     */
    private function writeAccessors(string $accessorStub, string $attributeName, string $attributeType): string
    {
        return str_replace(['{{ AttributeType }}', '{{ AttributeName }}', '{{ GetterName }}', '{{ SetterName }}'],
            [$attributeType, $attributeName, ucfirst($attributeName), ucfirst($attributeName)],
            $accessorStub);
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
        $entityNamespace = config('repository.path.namespace.entities');
        $relativeEntitiesPath = config('repository.path.relative.entities');
        $entityStubsPath = __DIR__ . '/../../' . config('repository.path.stub.entities');
        $phpVersion = config('repository.php_version');
        $filenameWithPath = $relativeEntitiesPath.$entityName.'.php';

        if ($this->option('delete')) {
            unlink($filenameWithPath);
            $this->info("Entity \"$entityName\" has been deleted.");
            return 0;
        }

        if ( ! file_exists($relativeEntitiesPath) && ! mkdir($relativeEntitiesPath, 0775, true) && ! is_dir($relativeEntitiesPath)) {
            $this->alert("Directory \"$relativeEntitiesPath\" was not created");
            return 0;
        }

        if (class_exists($relativeEntitiesPath.'\\'.$entityName) && ! $this->option('force')) {
            $this->alert("Entity \"$entityName\" is already exist!");
            return 0;
        }

        $columns = $this->getAllColumnsInTable($tableName);

        if ($columns->isEmpty()) {
            $this->alert("Couldn't retrieve columns from table \"$tableName\"! Perhaps table's name is misspelled.");
            die;
        }

        foreach ($columns as $_column) {
            $_column->COLUMN_NAME = camel_case($_column->COLUMN_NAME);
        }

        $baseContent = file_get_contents($entityStubsPath.'class.stub');
        $attributeStub = file_get_contents($entityStubsPath.'attribute.stub');
        $accessorsStub = file_get_contents($entityStubsPath.'accessors.stub');

        // Create Attributes
        $attributes = '';
        foreach ($columns as $_column) {
            $attributes .= $this->writeAttribute(
                $attributeStub,
                $_column->COLUMN_NAME,
                ($_column->IS_NULLABLE === 'YES' ? '?' : '') . $this->dataTypes[$_column->DATA_TYPE]
            );
        }

        // Create Setters and Getters
        $settersAndGetters = '';
        foreach ($columns as $_column) {
            $settersAndGetters .= $this->writeAccessors(
                $accessorsStub,
                $_column->COLUMN_NAME,
                ($_column->IS_NULLABLE === 'YES' ? '?' : '') . $this->dataTypes[$_column->DATA_TYPE]
            );
        }

        if ($detectForeignKeys) {
            $foreignKeys = $this->extractForeignKeys($tableName);

            // Create Additional Attributes from Foreign Keys
            foreach ($foreignKeys as $_foreignKey) {
                $attributes .= $this->writeAttribute(
                    $attributeStub,
                    $_foreignKey->VARIABLE_NAME,
                    $_foreignKey->ENTITY_DATA_TYPE
                );
            }

            // Create Additional Setters and Getters from Foreign keys
            foreach ($foreignKeys as $_foreignKey) {
                $settersAndGetters .= $this->writeAccessors(
                    $accessorsStub,
                    $_foreignKey->VARIABLE_NAME,
                    $_foreignKey->ENTITY_DATA_TYPE
                );
            }
        }

        $baseContent = str_replace(['{{ EntityNamespace }}', '{{ EntityName }}', '{{ Attributes }}', '{{ SettersAndGetters }}'],
            [$entityNamespace, $entityName, $attributes, $settersAndGetters],
            $baseContent);

        file_put_contents($filenameWithPath, $baseContent);

        if ($this->option('add-to-git')) {
            shell_exec('git add '.$filenameWithPath);
        }

        $this->info("Entity \"$entityName\" has been created.");

        return 0;
    }
}
