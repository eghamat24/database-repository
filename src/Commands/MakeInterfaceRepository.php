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
    protected $signature = 'command:make-interface-repository {table_name} {--k|foreign-keys : Detect foreign keys} {--d|delete : Delete resource} {--f|force : Override/Delete existing mysql repository}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new interface for repository';

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
        $interfaceName = "I$entityName" . "Repository";
        $interfaceNamespace = config('repository.path.namespace.repository');
        $relativeInterfacePath = config('repository.path.relative.repository') . "\\$entityName";

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

        // Initialize Interface
        $interfaceContent = "<?php\n\nnamespace $interfaceNamespace\\$entityName;\n\n";
        $interfaceContent .= "use App\Models\Entities\\$entityName;\n";
        $interfaceContent .= "use Illuminate\Support\Collection;\n\n";
        $interfaceContent .= "interface $interfaceName\n{";

        // Declare functions
        $interfaceContent .= "\n\tpublic function getOneById(int \$id): ?$entityName;\n";
        $interfaceContent .= "\n\tpublic function getAllByIds(array \$ids): Collection;\n";

        if ($detectForeignKeys) {
            foreach ($foreignKeys as $_foreignKey) {
                $foreignKeyInCamelCase = camel_case($_foreignKey->COLUMN_NAME);
                $interfaceContent .= "\n\tpublic function getOneBy" . ucfirst($foreignKeyInCamelCase) . "(int \$" . $foreignKeyInCamelCase . "): ?$entityName;\n";
                $interfaceContent .= "\n\tpublic function getAllBy" . ucfirst(str_plural($foreignKeyInCamelCase)) . "(array \$" . str_plural($foreignKeyInCamelCase) . "): Collection;\n";
            }
        }

        $allColumns = $columns->pluck('COLUMN_NAME')->toArray();

        $interfaceContent .= "\n\tpublic function create($entityName \$" . $entityVariableName . "): $entityName;\n";
        $interfaceContent .= "\n\tpublic function update($entityName \$" . $entityVariableName . "): int;\n";
        if (in_array('deleted_at', $allColumns)) {
            $interfaceContent .= "\n\tpublic function delete($entityName \$" . $entityVariableName . "): int;\n";
            $interfaceContent .= "\n\tpublic function undelete($entityName \$" . $entityVariableName . "): int;\n";
        }
        $interfaceContent .= "}";

        file_put_contents("$relativeInterfacePath/$interfaceName.php", $interfaceContent);

        shell_exec("git add $relativeInterfacePath/$interfaceName.php");

        $this->info("Interface \"$interfaceName\" has been created.");

        return 0;
    }
}
