<?php

namespace Eghamat24\DatabaseRepository\Commands;

use Illuminate\Support\Str;
use Eghamat24\DatabaseRepository\Creators\BaseCreator;
use Eghamat24\DatabaseRepository\Creators\CreatorRedisRepository;
use Eghamat24\DatabaseRepository\CustomMySqlQueries;
use Illuminate\Console\Command;

class MakeRedisRepository extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repository:make-redis-repository {table_name} {strategy}
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
    public function handle()
    {
        $this->checkStrategyName();
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

        $repositoryStubsPath = __DIR__ . '/../../' . config('repository.path.stub.repositories.base');
        $mysqlRepoCreator = new CreatorRedisRepository($redisRepositoryName,$redisRepositoryNamespace, $this->entityName,$this->strategyName,$repositoryStubsPath);
        $creator = new BaseCreator($mysqlRepoCreator);
        $baseContent = $creator->createClass($filenameWithPath,$this);
        $this->finalized($filenameWithPath, $redisRepositoryName, $baseContent);
    }
}
