<?php

namespace Changiz\DatabaseRepository\Commands;

use Changiz\DatabaseRepository\CustomMySqlQueries;
use Illuminate\Console\Command;

class MakeEntity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:make-entity {table_name} {--k|foreign-keys : Detect foreign keys} {--d|delete : Delete resource} {--f|force : Override/Delete existing mysql repository}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new entity.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    use CustomMySqlQueries;

    /**
     * Generate a getter for given attribute.
     * @param string $attributeName
     * @param string $attributeType
     * @return string
     */
    private function writeGetter(string $attributeName, string $attributeType): string
    {
        return "\n\t/**\n\t * @return $attributeType\n\t */\n\t" .
            "public function get" . ucfirst($attributeName) . "(): $attributeType\n\t" .
            "{\n\t\treturn \$this->$attributeName;\n\t}\n";
    }

    /**
     * Generate a setter for given attribute.
     * @param string $attributeName
     * @param string $attributeType
     * @return string
     */
    private function writeSetter(string $attributeName, string $attributeType): string
    {
        return "\n\t/**\n\t * @param $attributeType \$$attributeName\n\t */\n\t" .
            "public function set" . ucfirst($attributeName) . "($attributeType \$$attributeName): void\n\t" .
            "{\n\t\t\$this->$attributeName = \$$attributeName;\n\t}\n";
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
        $relativeEntitiesPath = config('repository.path.relative.entities');

        if ($this->option('delete')) {
            unlink("$relativeEntitiesPath/$entityName.php");
            $this->info("Entity \"$entityName\" has been deleted.");
            return 0;
        }

        if (!file_exists($relativeEntitiesPath)) {
            mkdir($relativeEntitiesPath, 775, true);
        }

        if (class_exists("$relativeEntitiesPath\\$entityName") && !$this->option('force')) {
            $this->alert("Entity $entityName is already exist!");
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

        foreach ($columns as $_column) {
            $_column->COLUMN_NAME = camel_case($_column->COLUMN_NAME);
        }

        // Initialize Class
        $entityContent = "<?php\n\nnamespace $relativeEntitiesPath;\n\n";
        $entityContent .= "class $entityName extends Entity\n{\n";

        // Create Attributes
        foreach ($columns as $_column) {
            $entityContent .= "\tprotected \$$_column->COLUMN_NAME;\n";
        }
        // Create Additional Attributes from Foreign Keys
        if ($detectForeignKeys) {
            foreach ($foreignKeys as $_foreignKey) {
                $entityContent .= "\n\tprotected \$" . $_foreignKey->VARIABLE_NAME . ";";
            }
            $entityContent .= "\n";
        }

        // Create Setters and Getters
        foreach ($columns as $_column) {
            $dataType = $this->dataTypes[$_column->DATA_TYPE];
            $entityContent .= $this->writeGetter($_column->COLUMN_NAME, $dataType);
            $entityContent .= $this->writeSetter($_column->COLUMN_NAME, $dataType);
        }
        // Create Additional Setters and Getters from Foreign keys
        if ($detectForeignKeys) {
            foreach ($foreignKeys as $_foreignKey) {
                $entityContent .= $this->writeGetter($_foreignKey->VARIABLE_NAME, $_foreignKey->ENTITY_DATA_TYPE);
                $entityContent .= $this->writeSetter($_foreignKey->VARIABLE_NAME, $_foreignKey->ENTITY_DATA_TYPE);
            }
        }
        $entityContent .= "}";

        file_put_contents("$relativeEntitiesPath/$entityName.php", $entityContent);

        shell_exec("git add $relativeEntitiesPath/$entityName.php");

        $this->info("Entity \"$entityName\" has been created.");

        return 0;
    }
}