<?php

namespace Eghamat24\DatabaseRepository\Commands;

use Illuminate\Support\Collection;
use Eghamat24\DatabaseRepository\Creators\BaseCreator;
use Eghamat24\DatabaseRepository\Creators\CreatorRedisRepository;
use Eghamat24\DatabaseRepository\CustomMySqlQueries;

class MakeRedisRepository extends BaseCommand
{
    use CustomMySqlQueries;

    private const OBJECT_NAME = 'Redis Repository';

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

    public function handle()
    {
        $this->checkStrategyName();
        $this->setArguments();

        $redisRepositoryName = "Redis$this->entityName" . 'Repository';
        $relativeRedisRepositoryPath = config('repository.path.relative.repositories') . $this->entityName . DIRECTORY_SEPARATOR;
        $filenameWithPath = $relativeRedisRepositoryPath . $redisRepositoryName . '.php';

        $this->checkAndPrepare($filenameWithPath, $redisRepositoryName, $relativeRedisRepositoryPath);
        $this->getColumnsOf($this->tableName);

        $redisRepositoryCreator = $this->getRedisRepositoryCreator($redisRepositoryName);
        $baseContent = $this->getBaseContent($redisRepositoryCreator, $filenameWithPath);

        $this->finalized($filenameWithPath, $redisRepositoryName, $baseContent);
    }


    private function getColumnsOf(string $tableName): Collection
    {
        $columns = $this->getAllColumnsInTable($tableName);
        $this->checkEmpty($columns, $tableName);

        return $columns;
    }


    private function getRedisRepositoryCreator(string $redisRepositoryName): CreatorRedisRepository
    {
        $redisRepositoryNamespace = config('repository.path.namespace.repositories');
        $repositoryStubsPath = __DIR__ . '/../../' . config('repository.path.stub.repositories.base');

        return new CreatorRedisRepository(
            $redisRepositoryName,
            $redisRepositoryNamespace,
            $this->entityName,
            $this->strategyName,
            $repositoryStubsPath
        );
    }

    /**
     * @param CreatorRedisRepository $redisRepositoryCreator
     * @param string $filenameWithPath
     * @return string
     */
    private function getBaseContent(CreatorRedisRepository $redisRepositoryCreator, string $filenameWithPath): string
    {
        $creator = new BaseCreator($redisRepositoryCreator);
        return $creator->createClass($filenameWithPath, $this);
    }

    /**
     * @param string $filenameWithPath
     * @param string $redisRepositoryName
     * @param string $relativeRedisRepositoryPath
     * @return void
     */
    private function checkAndPrepare(string $filenameWithPath, string $redisRepositoryName, string $relativeRedisRepositoryPath): void
    {
        $this->checkDelete($filenameWithPath, $redisRepositoryName, self::OBJECT_NAME);
        $this->checkDirectory($relativeRedisRepositoryPath);
        $this->checkClassExist($this->repositoryNamespace, $redisRepositoryName, self::OBJECT_NAME);
    }
}
