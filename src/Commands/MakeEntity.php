<?php

namespace Eghamat24\DatabaseRepository\Commands;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Eghamat24\DatabaseRepository\CustomMySqlQueries;
use Eghamat24\DatabaseRepository\Creators\CreatorEntity;
use Eghamat24\DatabaseRepository\Creators\BaseCreator;

class MakeEntity extends BaseCommand
{
    use CustomMySqlQueries;

    private const OBJECT_NAME = 'Entity';

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

        $this->checkAndPrepare($filenameWithPath);
        $columns = $this->getColumnsOf($this->tableName);

        foreach ($columns as $_column) {
            $_column->COLUMN_NAME = Str::camel($_column->COLUMN_NAME);
        }

        $entityCreator = $this->getCreatorEntity($columns);
        $baseContent = $this->getBaseContent($entityCreator, $filenameWithPath);

        $this->finalized($filenameWithPath, $this->entityName, $baseContent);
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
     * @param string $filenameWithPath
     * @return void
     */
    private function checkAndPrepare(string $filenameWithPath): void
    {
        $this->checkDelete($filenameWithPath, $this->entityName, self::OBJECT_NAME);
        $this->checkDirectory($this->relativeEntitiesPath);
        $this->checkClassExist($this->entityNamespace, $this->entityName, self::OBJECT_NAME);
    }

    /**
     * @param Collection $columns
     * @return CreatorEntity
     */
    private function getCreatorEntity(Collection $columns): CreatorEntity
    {
        return new CreatorEntity($columns,
            $this->detectForeignKeys,
            $this->tableName,
            $this->entityName,
            $this->entityNamespace,
            $this->entityStubsPath
        );
    }

    /**
     * @param CreatorEntity $entityCreator
     * @param string $filenameWithPath
     * @return string
     */
    private function getBaseContent(CreatorEntity $entityCreator, string $filenameWithPath): string
        $creator = new BaseCreator($entityCreator);
        return $creator->createClass($filenameWithPath, $this);
    }
}
