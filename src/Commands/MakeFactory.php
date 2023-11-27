<?php

namespace Eghamat24\DatabaseRepository\Commands;

use Illuminate\Support\Str;
use Eghamat24\DatabaseRepository\Creators\BaseCreator;
use Eghamat24\DatabaseRepository\Creators\CreatorFactory;
use Eghamat24\DatabaseRepository\CustomMySqlQueries;

class MakeFactory extends BaseCommand
{
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

    use CustomMySqlQueries;

    public function writeSetter(string $setterStub, string $columnName): string
    {
        return str_replace(['{{ SetterName }}', '{{ AttributeName }}'],
            [ucfirst($columnName), Str::snake($columnName)],
            $setterStub);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): void
    {
        $this->setArguments();

        $filenameWithPath = $this->relativeFactoriesPath . $this->factoryName . '.php';

        $this->checkDelete($filenameWithPath, $this->entityName, "Factory");
        $this->checkDirectory($this->relativeFactoriesPath);
        $this->checkClassExist($this->factoryNamespace, $this->entityName, "Factory");

        $columns = $this->getAllColumnsInTable($this->tableName);
        $this->checkEmpty($columns, $this->tableName);

        foreach ($columns as $_column) {
            $_column->COLUMN_NAME = Str::camel($_column->COLUMN_NAME);
        }

        $baseContent = file_get_contents($this->factoryStubsPath . 'class.stub');

        $factoryCreator = new CreatorFactory(
            $columns,
            $this->entityName,
            $this->entityNamespace,
            $this->factoryStubsPath,
            $this->factoryNamespace,
            $this->entityVariableName,
            $this->factoryName,
            $baseContent);
        $creator = new BaseCreator($factoryCreator);
        $baseContent = $creator->createClass($filenameWithPath, $this);

        $this->finalized($filenameWithPath, $this->factoryName, $baseContent);
    }
}
