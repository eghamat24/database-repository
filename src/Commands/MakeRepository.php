<?php

namespace Nanvaie\DatabaseRepository\Commands;

use Illuminate\Console\Command;
use Nanvaie\DatabaseRepository\CustomMySqlQueries;

class MakeRepository extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repository:make-repository {table_name}
    {--k|foreign-keys : Detect foreign keys}
    {--d|delete : Delete resource}
    {--f|force : Override/Delete existing repository class}
    {--g|add-to-git : Add created file to git repository}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new repository';

    use CustomMySqlQueries;

    private function writeFunction(string $functionStub, string $functionName, string $columnName, string $attributeType): string
    {
        if ($functionName === 'getOneBy') {
            $functionReturnType = '?{{ EntityName }}';
            $functionName .= ucfirst(camel_case($columnName));
            $columnName = camel_case($columnName);
        } elseif ($functionName === 'getAllBy') {
            $functionReturnType = 'Collection';
            $functionName .= ucfirst(str_plural(camel_case($columnName)));
            $columnName = str_plural(camel_case($columnName));
        } elseif ($functionName === 'create') {
            $functionReturnType = $attributeType;
        } elseif (in_array($functionName, ['update', 'remove', 'restore'])) {
            $functionReturnType = 'int';
        }

        return str_replace(['{{ FunctionName }}', '{{ AttributeType }}', '{{ AttributeName }}', '{{ FunctionReturnType }}'],
            [$functionName, $attributeType, camel_case($columnName), $functionReturnType],
            $functionStub);
    }

    private function writeSqlAttribute(string $attributeStub, string $sqlRepositoryVariable): string
    {
        return str_replace(['{{ SqlRepositoryVariable }}'],
            [$sqlRepositoryVariable],
            $attributeStub);
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
        $repositoryName = $entityName.'Repository';
        $sqlRepositoryName = 'MySql'.$entityName.'Repository';
        $sqlRepositoryVariable = 'mysqlRepository';
        $entityNamespace = config('repository.path.namespace.entities');
        $factoryNamespace = config('repository.path.namespace.factories');
        $repositoryNamespace = config('repository.path.namespace.repositories');
        $relativeRepositoryPath = config('repository.path.relative.repositories') . "\\$entityName";
        $repositoryStubsPath = config('repository.path.stub.repositories.base');
        $filenameWithPath = $relativeRepositoryPath.'\\'.$repositoryName.'.php';

        if ($this->option('delete')) {
            unlink("$relativeRepositoryPath/$repositoryName.php");
            $this->info("Repository \"$repositoryName\" has been deleted.");
            return 0;
        }

        if ( ! file_exists($relativeRepositoryPath) && ! mkdir($relativeRepositoryPath, 775, true) && ! is_dir($relativeRepositoryPath)) {
            $this->alert("Directory \"$relativeRepositoryPath\" was not created");
            return 0;
        }

        if (class_exists("$relativeRepositoryPath\\$repositoryName") && !$this->option('force')) {
            $this->alert("Repository $repositoryName is already exist!");
            return 0;
        }

        $columns = $this->getAllColumnsInTable($tableName);

        if ($columns->isEmpty()) {
            $this->alert("Couldn't retrieve columns from table " . $tableName . "! Perhaps table's name is misspelled.");
            die;
        }

        $baseContent = file_get_contents($repositoryStubsPath.'class.stub');
        $functionStub = file_get_contents($repositoryStubsPath.'function.stub');
        $attributeSqlStub = file_get_contents($repositoryStubsPath.'attribute.sql.stub');
        $setterSqlStub = file_get_contents($repositoryStubsPath.'setter.sql.stub');

        // Initialize Repository
        $attributes = '';
        $attributes = substr_replace($attributes,
            $this->writeSqlAttribute($attributeSqlStub, $sqlRepositoryVariable),
            -1, 0);

        $setters = '';
        $setters = substr_replace($setters,
            $this->writeSqlAttribute($setterSqlStub, $sqlRepositoryVariable),
            -1, 0);

        $functions = '';
        $functions = substr_replace($functions,
            $this->writeFunction($functionStub, 'getOneBy', 'id', 'int'),
            -1, 0);
        $functions = substr_replace($functions,
            $this->writeFunction($functionStub, 'getAllBy', 'id', 'array'),
            -1, 0);

        if ($detectForeignKeys) {
            $foreignKeys = $this->extractForeignKeys($tableName);

            foreach ($foreignKeys as $_foreignKey) {
                $functions = substr_replace($functions,
                    $this->writeFunction($functionStub, 'getOneBy', $_foreignKey->COLUMN_NAME, 'int'),
                    -1, 0);
                $functions = substr_replace($functions,
                    $this->writeFunction($functionStub, 'getAllBy', $_foreignKey->COLUMN_NAME, 'array'),
                    -1, 0);
            }
        }

        $functions = substr_replace($functions,
            $this->writeFunction($functionStub, 'create', $entityVariableName, $entityName),
            -1, 0);
        $functions = substr_replace($functions,
            $this->writeFunction($functionStub, 'update', $entityVariableName, $entityName),
            -1, 0);

        if (in_array('deleted_at', $columns->pluck('COLUMN_NAME')->toArray(), true)) {
            $functions = substr_replace($functions,
                $this->writeFunction($functionStub, 'remove', $entityVariableName, $entityName),
                -1, 0);
            $functions = substr_replace($functions,
                $this->writeFunction($functionStub, 'restore', $entityVariableName, $entityName),
                -1, 0);
        }

        $baseContent = str_replace(['{{ Attributes }}', '{{ Setters }}', '{{ Functions }}', '{{ EntityName }}', '{{ EntityNamespace }}', '{{ FactoryName }}', '{{ FactoryNamespace }}', '{{ EntityVariableName }}', '{{ RepositoryName }}', '{{ SqlRepositoryName }}', '{{ SqlRepositoryVariable }}', '{{ RepositoryNamespace }}', '{{ RepositoryInterfaceName }}', '{{ TableName }}'],
            [$attributes, $setters, $functions, $entityName, $entityNamespace, $factoryName, $factoryNamespace, $entityVariableName, $repositoryName, $sqlRepositoryName, $sqlRepositoryVariable, $repositoryNamespace, $interfaceName, $tableName],
            $baseContent);

        file_put_contents($filenameWithPath, $baseContent);

        if ($this->option('add-to-git')) {
            shell_exec("git add $filenameWithPath");
        }

        $this->info("Repository \"$repositoryName\" has been created.");

        return 0;
    }
}
