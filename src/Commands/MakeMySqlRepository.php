<?php

namespace Eghamat24\DatabaseRepository\Commands;

use Eghamat24\DatabaseRepository\Creators\BaseCreator;
use Eghamat24\DatabaseRepository\Creators\CreatorMySqlRepository;
use Eghamat24\DatabaseRepository\CustomMySqlQueries;
use Illuminate\Support\Collection;

class MakeMySqlRepository extends BaseCommand
{
    use CustomMySqlQueries;

    private const OBJECT_NAME = 'MySql Repository';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repository:make-mysql-repository {table_name}
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


    public function handle(): void
    {
        $this->setArguments();

        $filenameWithPath = $this->getFileNameWithPath(
            $this->relativeMysqlRepositoryPath,
            $this->mysqlRepositoryName
        );

        $this->checkAndPrepare($filenameWithPath);

        $this->finalized(
            $filenameWithPath,
            $this->mysqlRepositoryName,
            $this->generateBaseContent($filenameWithPath)
        );
    }


    private function getFileNameWithPath(string $relativePath, string $sectionName): string
    {
        return $relativePath . $sectionName . '.php';
    }


    private function checkAndPrepare(string $filenameWithPath)
    {
        $this->checkDelete($filenameWithPath, $this->mysqlRepositoryName, self::OBJECT_NAME);
        $this->checkDirectory($this->relativeMysqlRepositoryPath);
        $this->checkClassExist($this->repositoryNamespace, $this->mysqlRepositoryName, self::OBJECT_NAME);
    }


    private function getColumnsOf(string $tableName): Collection
    {
        $columns = $this->getAllColumnsInTable($tableName);
        $this->checkEmpty($columns, $tableName);

        return $columns;
    }


    private function generateBaseContent(string $filenameWithPath): string
    {
        $mysqlRepoCreator = $this->makeMySqlRepoCreator();

        return (new BaseCreator($mysqlRepoCreator))->createClass($filenameWithPath, $this);
    }

    /**
     * @return CreatorMySqlRepository
     */
    private function makeMySqlRepoCreator(): CreatorMySqlRepository
    {
        return new CreatorMySqlRepository(
            $this->getColumnsOf($this->tableName),
            $this->tableName,
            $this->entityName,
            $this->entityVariableName,
            $this->factoryName,
            $this->entityNamespace,
            $this->factoryNamespace,
            $this->mysqlRepositoryName,
            $this->repositoryNamespace,
            $this->interfaceName,
            $this->mysqlRepositoryStubsPath,
            $this->detectForeignKeys
        );
    }
}
