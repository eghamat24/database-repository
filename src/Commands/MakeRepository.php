<?php

namespace Eghamat24\DatabaseRepository\Commands;

use Illuminate\Support\Collection;
use Eghamat24\DatabaseRepository\Creators\BaseCreator;
use Eghamat24\DatabaseRepository\Creators\CreatorRepository;
use Eghamat24\DatabaseRepository\CustomMySqlQueries;

class MakeRepository extends BaseCommand
{
    use CustomMySqlQueries;

    private const OBJECT_NAME = 'Repository';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repository:make-repository {table_name} {strategy} {selected_db?}
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

    public function handle(): void
    {
        $this->checkDatabasesExist();
        $this->checkStrategyName();

        $this->setArguments();
        $repositoryName = $this->entityName . self::OBJECT_NAME;
        $relativeRepositoryPath = config('repository.path.relative.repositories') . "$this->entityName" . DIRECTORY_SEPARATOR;

        $filenameWithPath = $relativeRepositoryPath . $repositoryName . '.php';
        $this->checkAndPrepare($filenameWithPath, $repositoryName, $relativeRepositoryPath);

        $repositoryCreator = $this->getRepositoryCreator($repositoryName);
        $baseContent = $this->createBaseContent($repositoryCreator, $filenameWithPath);

        $this->finalized($filenameWithPath, $repositoryName, $baseContent);
    }

    /**
     * @param string $repositoryName
     * @return CreatorRepository
     */
    private function getRepositoryCreator(string $repositoryName): CreatorRepository
    {
        $sqlRepositoryName = ucwords($this->selectedDb) . $repositoryName;
        $repositoryStubsPath = __DIR__ . '/../../' . config('repository.path.stub.repositories.base');

        return new CreatorRepository(
            $this->getColumnsOf($this->tableName),
            'repository',
            $sqlRepositoryName,
            $repositoryStubsPath,
            $this->detectForeignKeys,
            $this->tableName,
            $this->entityVariableName,
            $this->entityName,
            $this->entityNamespace,
            $repositoryName,
            $this->interfaceName,
            $this->repositoryNamespace,
            $this->selectedDb,
            'redisRepository',
            'Redis' . $repositoryName,
            $this->strategyName
        );
    }

    /**
     * @param string $tableName
     * @return Collection
     */
    private function getColumnsOf(string $tableName): Collection
    {
        $columns = $this->getAllColumnsInTable($tableName);
        $this->checkEmpty($columns, $tableName);

        return $columns;
    }

    /**
     * @param CreatorRepository $RepoCreator
     * @param string $filenameWithPath
     * @return string
     */
    private function createBaseContent(CreatorRepository $RepoCreator, string $filenameWithPath): string
    {
        $creator = new BaseCreator($RepoCreator);
        return $creator->createClass($filenameWithPath, $this);
    }

    /**
     * @param string $filenameWithPath
     * @param string $repositoryName
     * @param string $relativeRepositoryPath
     * @return void
     */
    private function checkAndPrepare(string $filenameWithPath, string $repositoryName, string $relativeRepositoryPath): void
    {
        $this->checkDelete($filenameWithPath, $repositoryName, self::OBJECT_NAME);
        $this->checkDirectory($relativeRepositoryPath);
        $this->checkClassExist($this->repositoryNamespace, $repositoryName, self::OBJECT_NAME);
    }
}
