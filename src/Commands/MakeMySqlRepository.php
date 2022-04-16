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
    protected $signature = 'command:make-mysql-repository {table_name}
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
     * @param string $functionName
     * @param string $entityName
     * @return string
     */
    private function createGetOneFunction(string $functionName, string $entityName): string
    {
        $functionName = camel_case($functionName);
        $entityVariableName = camel_case($entityName);

        return "\n\t/**\n\t * @param int \$$functionName\n\t * @return $entityName|null\n\t */\n\t" .
            "public function getOneBy" . ucfirst($functionName) . "(int \$$functionName): ?$entityName\n\t" .
            "{\n\t\t\$$entityVariableName = \$this->newQuery()" .
            "\n\t\t\t->where('" . snake_case($functionName) . "', \$$functionName)" .
            "\n\t\t\t->first();" .
            "\n\n\t\treturn \$$entityVariableName ? \$this->factory->makeEntityFromStdClass(\$$entityVariableName) : null;\n\t}\n";
    }

    /**
     * @param string $functionName
     * @param string $entityName
     * @return string
     */
    private function createGetAllFunction(string $functionName, string $entityName): string
    {
        $functionNamePlural = str_plural(camel_case($functionName));
        $entityVariableNamePlural = str_plural(camel_case($entityName));

        return "\n\t/**\n\t * @param array \$$functionNamePlural\n\t * @return Collection\n\t */\n\t" .
            "public function getAllBy" . ucfirst($functionNamePlural) . "(array \$$functionNamePlural): Collection\n\t" .
            "{\n\t\t\$$entityVariableNamePlural = \$this->newQuery()" .
            "\n\t\t\t->whereIn('$functionName', \$$functionNamePlural)" .
            "\n\t\t\t->get();" .
            "\n\n\t\treturn \$this->factory->makeCollectionOfEntities(\$$entityVariableNamePlural);\n\t}\n";
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
        $factoryName = $entityName . "Factory";
        $interfaceName = "I$entityName" . "Repository";
        $mysqlRepositoryName = "MySql$entityName" . "Repository";
        $mysqlRepositoryNamespace = config('repository.path.namespace.repository');
        $relativeMysqlRepositoryPath = config('repository.path.relative.repository') . "\\$entityName";

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

        // Initialize MySql Repository
        $mysqlRepositoryContent = "<?php\n\nnamespace $mysqlRepositoryNamespace\\$entityName;\n\n";
        $mysqlRepositoryContent .= "use App\Models\Entities\\$entityName;\n";
        $mysqlRepositoryContent .= "use App\Models\Factories\\$factoryName;\n";
        $mysqlRepositoryContent .= "use Nanvaie\DatabaseRepository\Models\Repository\MySqlRepository;\n";
        $mysqlRepositoryContent .= "use Illuminate\Support\Collection;\n\n";
        $mysqlRepositoryContent .= "class $mysqlRepositoryName extends MySqlRepository implements $interfaceName\n{";

        // Define Constructor
        $mysqlRepositoryContent .= "\n\t/**\n\t * $mysqlRepositoryName constructor.\n\t */";
        $mysqlRepositoryContent .= "\n\tpublic function __construct()\n\t{";
        $mysqlRepositoryContent .= "\n\t\t\$this->table = '$tableName';";
        if (in_array('deleted_at', $columns->pluck('COLUMN_NAME')->toArray())) {
            $mysqlRepositoryContent .= "\n\t\t\$this->softDelete = true;";
        }
        $mysqlRepositoryContent .= "\n\t\t\$this->factory = new $factoryName();";
        $mysqlRepositoryContent .= "\n\n\t\tparent::__construct();\n\t}\n";

        // Define getOneBy and getAllBy Functions
        $mysqlRepositoryContent .= $this->createGetOneFunction('id', $entityName);
        $mysqlRepositoryContent .= $this->createGetAllFunction('id', $entityName);

        if ($detectForeignKeys) {
            foreach ($foreignKeys as $_foreignKey) {
                $mysqlRepositoryContent .= $this->createGetOneFunction($_foreignKey->COLUMN_NAME, $entityName);
                $mysqlRepositoryContent .= $this->createGetAllFunction($_foreignKey->COLUMN_NAME, $entityName);
            }
        }

        // Create "create" Function
        $mysqlRepositoryContent .= "\n\t/**\n\t * @param $entityName \$$entityVariableName\n\t * @return $entityName\n\t */";
        $mysqlRepositoryContent .= "\n\tpublic function create($entityName \$$entityVariableName): $entityName\n\t{";
        $mysqlRepositoryContent .= "\n\t\t\$id = \$this->newQuery()";
        $mysqlRepositoryContent .= "\n\t\t\t->insertGetId([";
        foreach ($columns as $_column) {
            if (!in_array($_column->COLUMN_NAME, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                $mysqlRepositoryContent .= "\n\t\t\t\t'" . $_column->COLUMN_NAME . "' => \$" . $entityVariableName . "->get" . ucfirst(camel_case($_column->COLUMN_NAME)) . "(),";
            } elseif ($_column->COLUMN_NAME === 'created_at') {
                $mysqlRepositoryContent .= "\n\t\t\t\t'" . $_column->COLUMN_NAME . "' => date('Y-m-d H:i:s'),";
            }
        }
        $mysqlRepositoryContent .= "\n\t\t\t]);\n";
        $mysqlRepositoryContent .= "\n\t\t\$" . $entityVariableName . "->setId(\$id);\n";
        $mysqlRepositoryContent .= "\n\t\treturn \$$entityVariableName;\n\t}\n";

        // Create "update" Function
        $mysqlRepositoryContent .= "\n\t/**\n\t * @param $entityName \$$entityVariableName\n\t * @return int\n\t */";
        $mysqlRepositoryContent .= "\n\tpublic function update($entityName \$$entityVariableName): int\n\t{";
        $mysqlRepositoryContent .= "\n\t\treturn \$this->newQuery()";
        $mysqlRepositoryContent .= "\n\t\t\t->where(\$this->primaryKey, \$" . $entityVariableName . "->getPrimaryKey())";
        $mysqlRepositoryContent .= "\n\t\t\t->update([";
        foreach ($columns as $_column) {
            if (!in_array($_column->COLUMN_NAME, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                $mysqlRepositoryContent .= "\n\t\t\t\t'" . $_column->COLUMN_NAME . "' => \$" . $entityVariableName . "->get" . ucfirst(camel_case($_column->COLUMN_NAME)) . "(),";
            } elseif ($_column->COLUMN_NAME === 'updated_at') {
                $mysqlRepositoryContent .= "\n\t\t\t\t'" . $_column->COLUMN_NAME . "' => date('Y-m-d H:i:s'),";
            }
        }
        $mysqlRepositoryContent .= "\n\t\t\t]);\n\t}\n";

        // Create "delete" and "undelete" Function
        if (in_array('deleted_at', $columns->pluck('COLUMN_NAME')->toArray())) {
            $mysqlRepositoryContent .= "\n\t/**\n\t * @param $entityName \$$entityVariableName\n\t * @return int\n\t */";
            $mysqlRepositoryContent .= "\n\tpublic function delete($entityName \$$entityVariableName): int\n\t{";
            $mysqlRepositoryContent .= "\n\t\treturn \$this->newQuery()";
            $mysqlRepositoryContent .= "\n\t\t\t->where(\$this->primaryKey, \$" . $entityVariableName . "->getPrimaryKey())";
            $mysqlRepositoryContent .= "\n\t\t\t->update([";
            $mysqlRepositoryContent .= "\n\t\t\t\t'deleted_at' => date('Y-m-d H:i:s'),";
            $mysqlRepositoryContent .= "\n\t\t\t]);\n\t}\n";

            $mysqlRepositoryContent .= "\n\t/**\n\t * @param $entityName \$$entityVariableName\n\t * @return int\n\t */";
            $mysqlRepositoryContent .= "\n\tpublic function undelete($entityName \$$entityVariableName): int\n\t{";
            $mysqlRepositoryContent .= "\n\t\treturn \$this->newQuery()";
            $mysqlRepositoryContent .= "\n\t\t\t->where(\$this->primaryKey, \$" . $entityVariableName . "->getPrimaryKey())";
            $mysqlRepositoryContent .= "\n\t\t\t->update([";
            $mysqlRepositoryContent .= "\n\t\t\t\t'deleted_at' => null,";
            $mysqlRepositoryContent .= "\n\t\t\t]);\n\t}\n";
        }

        $mysqlRepositoryContent .= "}";

        file_put_contents("$relativeMysqlRepositoryPath/$mysqlRepositoryName.php", $mysqlRepositoryContent);

        if ($this->option('add-to-git')) {
            shell_exec("git add $relativeMysqlRepositoryPath/$mysqlRepositoryName.php");
        }

        $this->info("MySql Repository \"$mysqlRepositoryName\" has been created.");

        return 0;
    }
}