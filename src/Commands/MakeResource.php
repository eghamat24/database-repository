<?php

namespace Nanvaie\DatabaseRepository\Commands;

use Nanvaie\DatabaseRepository\CustomMySqlQueries;
use Illuminate\Console\Command;

class MakeResource extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:make-resource {table_name}
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
        $detectForeignKeys = $this->option('foreign-keys');
        $entityName = str_singular(ucfirst(camel_case($tableName)));
        $entityVariableName = camel_case($entityName);
        $resourceName = $entityName . "Resource";
        $resourceNamespace = config('repository.path.namespace.resource');
        $relativeResourcesPath = config('repository.path.relative.resource');

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

        // Initialize Class
        $resourceContent = "<?php\n\nnamespace $resourceNamespace;\n\n";
        $resourceContent .= "use App\Http\Resources\Resource;\n";
        $resourceContent .= "use App\Models\Entities\\$entityName;\n\n";
        $resourceContent .= "class $resourceName extends Resource\n{\n";

        // Create "toArray" Function
        $resourceContent .= "\t/**\n\t * @param $entityName \$$entityVariableName\n\t * @return array\n\t */\n";
        $resourceContent .= "\tpublic function toArray($entityName \$$entityVariableName): array\n\t{\n";
        $resourceContent .= " \t\treturn [";
        foreach ($columns as $_column) {
            $resourceContent .= "\n\t\t\t'$_column->COLUMN_NAME' => \$$entityVariableName" . "->get" . ucfirst(camel_case($_column->COLUMN_NAME)) . "(),";
        }

        // Add Additional Resources for Foreign Keys
        if ($detectForeignKeys) {
            $resourceContent .= "\n";
            foreach ($foreignKeys as $_foreignKey) {
                $resourceContent .= "\n\t\t\t'" . snake_case($_foreignKey->VARIABLE_NAME) .
                    "' => \$$entityVariableName" . "->get" . ucfirst($_foreignKey->VARIABLE_NAME) . "()" .
                    " ? (new " . $_foreignKey->ENTITY_DATA_TYPE . "Resource())->toArray(\$$entityVariableName" .
                    "->get" . ucfirst($_foreignKey->VARIABLE_NAME) . "()) : null,";
            }
        }
        $resourceContent .= "\n\t\t];\n\t}\n";

        $resourceContent .= "}";

        file_put_contents("$relativeResourcesPath/$resourceName.php", $resourceContent);

        if ($this->option('add-to-git')) {
            shell_exec("git add $relativeResourcesPath/$resourceName.php");
        }

        $this->info("Resource \"$resourceName\" has been created.");

        return 0;
    }
}
