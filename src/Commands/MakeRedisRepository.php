<?php

namespace Nanvaie\DatabaseRepository\Commands;

use Nanvaie\DatabaseRepository\CustomMySqlQueries;
use Illuminate\Console\Command;

class MakeRedisRepository extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repository:make-redis-repository {table_name}
    {--k|foreign-keys : Detect foreign keys}
    {--d|delete : Delete resource}
    {--f|force : Override/Delete existing redis repository}
    {--g|add-to-git : Add created file to git repository}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Redis repository class';

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
        $factoryName = $entityName . "Factory";
        $interfaceName = "I$entityName" . "Repository";
        $redisRepositoryName = "Redis$entityName" . "Repository";
        $redisRepositoryNamespace = config('repository.path.namespace.repositories');
        $relativeRedisRepositoryPath = config('repository.path.relative.repositories') . "\\$entityName";

        if ($this->option('delete')) {
            unlink("$relativeRedisRepositoryPath/$redisRepositoryName.php");
            $this->info("Redis Repository \"$redisRepositoryName\" has been deleted.");
            return 0;
        }

        if ( ! file_exists($relativeRedisRepositoryPath) && ! mkdir($relativeRedisRepositoryPath) && ! is_dir($relativeRedisRepositoryPath)) {
            $this->alert("Directory \"$relativeRedisRepositoryPath\" was not created");
            return 0;
        }

        if (class_exists("$relativeRedisRepositoryPath\\$redisRepositoryName") && !$this->option('force')) {
            $this->alert("Repository $redisRepositoryName is already exist!");
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

        // Initialize Redis Repository
        $redisRepositoryContent = "<?php\n\nnamespace $redisRepositoryNamespace\\$entityName;\n\n";
        $redisRepositoryContent .= "use Nanvaie\DatabaseRepository\Models\Repository\RedisRepository;\n\n";
        $redisRepositoryContent .= "class $redisRepositoryName extends RedisRepository\n{";
        $redisRepositoryContent .= "}";

        file_put_contents("$relativeRedisRepositoryPath/$redisRepositoryName.php", $redisRepositoryContent);

        if ($this->option('add-to-git')) {
            shell_exec("git add $relativeRedisRepositoryPath/$redisRepositoryName.php");
        }

        $this->info("Redis Repository \"$redisRepositoryName\" has been created.");

        return 0;
    }
}
