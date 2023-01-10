<?php

namespace Nanvaie\DatabaseRepository\Commands;

use Illuminate\Support\Str;
use Nanvaie\DatabaseRepository\Creators\BaseCreator;
use Nanvaie\DatabaseRepository\Creators\CreatorRedisRepository;
use Nanvaie\DatabaseRepository\CustomMySqlQueries;
use Illuminate\Console\Command;

class MakeRedisRepository extends BaseCommand
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
    public function handle(): void
    {
        $this->setArguments();
        $redisRepositoryName = "Redis$this->entityName"."Repository";
        $redisRepositoryNamespace = config('repository.path.namespace.repositories');
        $relativeRedisRepositoryPath = config('repository.path.relative.repositories') . "$this->entityName" . DIRECTORY_SEPARATOR;
        $filenameWithPath = $relativeRedisRepositoryPath . $redisRepositoryName . '.php';

        $this->checkDelete($filenameWithPath,$redisRepositoryName,"Redis Repository");
        $this->checkDirectory($relativeRedisRepositoryPath);
        $this->checkClassExist($this->repositoryNamespace,$redisRepositoryName,"Redis Repository");

        $columns = $this->getAllColumnsInTable($this->tableName);
        $this->checkEmpty($columns,$this->tableName);

        if ($this->detectForeignKeys) {
            $foreignKeys = $this->extractForeignKeys($this->tableName);
        }

        $mysqlRepoCreator = new CreatorRedisRepository($redisRepositoryName,$redisRepositoryNamespace, $this->entityName);
        $creator = new BaseCreator($mysqlRepoCreator);
        $baseContent = $creator->createClass($filenameWithPath,$this);

        $this->finalized($filenameWithPath, $redisRepositoryName, $baseContent);

    }
}
