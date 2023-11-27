<?php

namespace Eghamat24\DatabaseRepository\Commands;

use Illuminate\Support\Str;
use Eghamat24\DatabaseRepository\CreateEntity;
use Eghamat24\DatabaseRepository\CustomMySqlQueries;
use Eghamat24\DatabaseRepository\Creators\CreatorEntity;
use Eghamat24\DatabaseRepository\Creators\BaseCreator;
use Illuminate\Support\Collection;

class MakeEntity extends BaseCommand
{
    use CustomMySqlQueries;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repository:make-entity {table_name}
    {--k|foreign-keys : Detect foreign keys}
    {--d|delete : Delete resource}
    {--f|force : Override/Delete existing mysql repository}
    {--g|add-to-git : Add created file to git repository}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new entity.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->setArguments();
        $filenameWithPath = $this->relativeEntitiesPath . $this->entityName . '.php';

        $this->checkDelete($filenameWithPath, $this->entityName, "Entity");
        $this->checkDirectory($this->relativeEntitiesPath);
        $this->checkClassExist($this->entityNamespace, $this->entityName, "Entity");

        $columns = $this->getAllColumnsInTable($this->tableName);
        $this->checkEmpty($columns, $this->tableName);

        foreach ($columns as $_column) {
            $_column->COLUMN_NAME = Str::camel($_column->COLUMN_NAME);
        }

        $entityCreator = new CreatorEntity($columns,
            $this->detectForeignKeys,
            $this->tableName,
            $this->entityName,
            $this->entityNamespace,
            $this->entityStubsPath
        );
        $creator = new BaseCreator($entityCreator);
        $baseContent = $creator->createClass($filenameWithPath, $this);

        $this->finalized($filenameWithPath, $this->entityName, $baseContent);

    }
}
