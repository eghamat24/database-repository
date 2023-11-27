<?php

namespace Eghamat24\DatabaseRepository\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Eghamat24\DatabaseRepository\Creators\BaseCreator;
use Eghamat24\DatabaseRepository\Creators\CreatorResource;
use Eghamat24\DatabaseRepository\CustomMySqlQueries;

class MakeResource extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repository:make-resource {table_name}
    {--k|foreign-keys : Detect foreign keys}
    {--d|delete : Delete resource}
    {--f|force : Override/Delete existing mysql repository}
    {--g|add-to-git : Add created file to git repository}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create new resource';

    use CustomMySqlQueries;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): void
    {
        $this->setArguments();
        $resourceName = $this->entityName . "Resource";
        $resourceNamespace = config('repository.path.namespace.resources');
        $relativeResourcesPath = config('repository.path.relative.resources');
        $resourceStubsPath = __DIR__ . '/../../' . config('repository.path.stub.resources');
        $filenameWithPath = $relativeResourcesPath . $resourceName . '.php';

        $this->checkDelete($filenameWithPath, $resourceName, "Resource");
        $this->checkDirectory($relativeResourcesPath);
        $this->checkClassExist($resourceNamespace, $resourceName, "Resource");

        $columns = $this->getAllColumnsInTable($this->tableName);
        $this->checkEmpty($columns, $this->tableName);

        $RepoCreator = new CreatorResource($columns,
            $this->tableName,
            $this->entityName,
            $this->entityNamespace,
            $resourceNamespace,
            $resourceName,
            $resourceStubsPath,
            $this->detectForeignKeys,
            $this->entityVariableName);
        $creator = new BaseCreator($RepoCreator);
        $baseContent = $creator->createClass($filenameWithPath, $this);
        $this->finalized($filenameWithPath, $resourceName, $baseContent);

    }

}
