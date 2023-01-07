<?php

namespace Nanvaie\DatabaseRepository\Commands;

use Illuminate\Support\Str;
use Nanvaie\DatabaseRepository\CreateEntity;
use Nanvaie\DatabaseRepository\CustomMySqlQueries;
use Nanvaie\DatabaseRepository\Creators\CreatorEntity;
use Nanvaie\DatabaseRepository\Creators\BaseCreator;
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
    public function handle(): int
    {
        $tableName = $this->argument('table_name');
        $detectForeignKeys = $this->option('foreign-keys');
        $entityName = Str::singular(ucfirst(Str::camel($tableName)));
        $entityNamespace = config('repository.path.namespace.entities');
        $relativeEntitiesPath = config('repository.path.relative.entities');
        $entityStubsPath = __DIR__ . '/../../' . config('repository.path.stub.entities');
        $filenameWithPath = $relativeEntitiesPath . $entityName.'.php';

        $this->checkDelete($filenameWithPath,$entityName);
        $this->checkDirectory($relativeEntitiesPath,$entityName);
        $this->checkClassExist($relativeEntitiesPath,$entityName);

        $columns = $this->getAllColumnsInTable($tableName);
        $this->checkEmpty($columns,$tableName);

        foreach ($columns as $_column) {
            $_column->COLUMN_NAME = Str::camel($_column->COLUMN_NAME);
        }

        $baseContent = file_get_contents($entityStubsPath.'class.stub');
        $attributeStub = file_get_contents($entityStubsPath.'attribute.stub');
        $accessorsStub = file_get_contents($entityStubsPath.'accessors.stub');

        $entityCreator = new CreatorEntity($columns,$attributeStub, $detectForeignKeys,$tableName,$entityName,$entityNamespace,$accessorsStub,$baseContent);
        $creator = new BaseCreator($entityCreator);
        $baseContent = $creator->createClass();

        $this->finalized($filenameWithPath, $entityName, $baseContent);
        return 0;
    }


}
