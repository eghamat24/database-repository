<?php

namespace Eghamat24\DatabaseRepository\Commands;

use Illuminate\Support\Str;
use Eghamat24\DatabaseRepository\Creators\BaseCreator;
use Eghamat24\DatabaseRepository\Creators\CreatorEntity;
use Eghamat24\DatabaseRepository\Creators\CreatorMySqlRepository;
use Eghamat24\DatabaseRepository\CustomMySqlQueries;
use Illuminate\Console\Command;

class MakeMySqlRepository extends BaseCommand
{
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

    use CustomMySqlQueries;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): void
    {
        $this->setArguments();
        $filenameWithPath = $this->relativeMysqlRepositoryPath . $this->mysqlRepositoryName . '.php';
        $this->checkDelete($filenameWithPath, $this->mysqlRepositoryName, "MySql Repository");
        $this->checkDirectory($this->relativeMysqlRepositoryPath);
        $this->checkClassExist($this->repositoryNamespace, $this->mysqlRepositoryName, "MySql Repository");
        $columns = $this->getAllColumnsInTable($this->tableName);
        $this->checkEmpty($columns, $this->tableName);

        $mysqlRepoCreator = new CreatorMySqlRepository($columns,
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
        $creator = new BaseCreator($mysqlRepoCreator);
        $baseContent = $creator->createClass($filenameWithPath, $this);

        $this->finalized($filenameWithPath, $this->mysqlRepositoryName, $baseContent);
    }
}
