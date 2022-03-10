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
    protected $signature = 'command:make-factory {table_name} {--d|delete : Delete resource} {--f|force : Override/Delete existing factory class}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new factory.';

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
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $tableName = $this->argument('table_name');
        $entityName = str_singular(ucfirst(camel_case($tableName)));
        $entityVariableName = camel_case($entityName);
        $factoryName = $entityName . "Factory";
        $relativeFactoriesPath = config('repository.path.relative.factories');

        if ($this->option('delete')) {
            unlink("$relativeFactoriesPath/$factoryName.php");
            $this->info("Factory \"$factoryName\" has been deleted.");
            return 0;
        }

        if (!file_exists($relativeFactoriesPath)) {
            mkdir($relativeFactoriesPath, 775, true);
        }

        if (class_exists("$relativeFactoriesPath\\$factoryName") && !$this->option('force')) {
            $this->alert("Factory $factoryName is already exist!");
            die;
        }

        $columns = $this->getAllColumnsInTable($tableName);

        if ($columns->isEmpty()) {
            $this->alert("Couldn't retrieve columns from table " . $tableName . "! Perhaps table's name is misspelled.");
            die;
        }

        // Initialize Class
        $factoryContent = "<?php\n\nnamespace $relativeFactoriesPath;\n\n";
        $factoryContent .= "use App\Models\Entities\\$entityName;\nuse stdClass;\n\n";
        $factoryContent .= "class $factoryName extends Factory\n{\n";

        // Create "makeEntityFromStdClass" Function
        $factoryContent .= "\t/**\n\t * @param stdClass \$entity\n\t * @return $entityName\n\t */\n";
        $factoryContent .= "\tpublic function makeEntityFromStdClass(stdClass \$entity): $entityName\n\t{\n";
        $factoryContent .= "\t\t\$$entityVariableName = new $entityName();\n";
        foreach ($columns as $_column) {
            $factoryContent .= "\n\t\t\$" . $entityVariableName . "->set" . ucfirst(camel_case($_column->COLUMN_NAME)) . "(\$entity->" . snake_case($_column->COLUMN_NAME) . " ?? null);";
        }
        $factoryContent .= "\n\n\t\treturn \$$entityVariableName;\n\t}\n}";

        file_put_contents("$relativeFactoriesPath/$factoryName.php", $factoryContent);

        shell_exec("git add $relativeFactoriesPath/$factoryName.php");

        $this->info("Factory \"$factoryName\" has been created.");

        return 0;
    }
}