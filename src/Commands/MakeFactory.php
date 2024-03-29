<?php

namespace Eghamat24\DatabaseRepository\Commands;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Eghamat24\DatabaseRepository\Creators\BaseCreator;
use Eghamat24\DatabaseRepository\Creators\CreatorFactory;
use Eghamat24\DatabaseRepository\CustomMySqlQueries;

class MakeFactory extends BaseCommand
{
    use CustomMySqlQueries;

    private const OBJECT_NAME = 'Factory';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repository:make-factory {table_name}
    {--d|delete : Delete resource}
    {--f|force : Override/Delete existing factory class}
    {--g|add-to-git : Add created file to git repository}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new factory.';

    public function writeSetter(string $setterStub, string $columnName): string
    {
        return str_replace(['{{ SetterName }}', '{{ AttributeName }}'],
            [ucfirst($columnName), Str::snake($columnName)],
            $setterStub);
    }

    public function handle(): void
    {
        $this->setArguments();

        $filenameWithPath = $this->relativeFactoriesPath . $this->factoryName . '.php';

        $this->checkAndPrepare($filenameWithPath);

        $columns = $this->getColumnsOf($this->tableName);

        foreach ($columns as $_column) {
            $_column->COLUMN_NAME = Str::camel($_column->COLUMN_NAME);
        }

        $baseContent = file_get_contents($this->factoryStubsPath . 'class.stub');

        $factoryCreator = $this->getCreatorFactory($columns, $baseContent);
        $baseContent = $this->generateBaseContent($factoryCreator, $filenameWithPath);

        $this->finalized($filenameWithPath, $this->factoryName, $baseContent);
    }

    /**
     * @param string $filenameWithPath
     * @return void
     */
    public function checkAndPrepare(string $filenameWithPath): void
    {
        $this->checkDelete($filenameWithPath, $this->entityName, self::OBJECT_NAME);
        $this->checkDirectory($this->relativeFactoriesPath);
        $this->checkClassExist($this->factoryNamespace, $this->entityName, self::OBJECT_NAME);
    }

    /**
     * @param string $tableName
     * @return Collection
     */
    public function getColumnsOf(string $tableName): Collection
    {
        $columns = $this->getAllColumnsInTable($tableName);
        $this->checkEmpty($columns, $tableName);

        return $columns;
    }

    /**
     * @param Collection $columns
     * @param bool|string $baseContent
     * @return CreatorFactory
     */
    public function getCreatorFactory(Collection $columns, bool|string $baseContent): CreatorFactory
    {
        return new CreatorFactory(
            $columns,
            $this->entityName,
            $this->entityNamespace,
            $this->factoryStubsPath,
            $this->factoryNamespace,
            $this->entityVariableName,
            $this->factoryName,
            $baseContent
        );
    }

    /**
     * @param CreatorFactory $factoryCreator
     * @param string $filenameWithPath
     * @return string
     */
    public function generateBaseContent(CreatorFactory $factoryCreator, string $filenameWithPath): string
    {
        $creator = new BaseCreator($factoryCreator);
        return $creator->createClass($filenameWithPath, $this);
    }
}
