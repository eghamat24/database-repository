<?php

namespace Eghamat24\DatabaseRepository\Commands;

use Eghamat24\DatabaseRepository\Creators\BaseCreator;
use Eghamat24\DatabaseRepository\Creators\CreatorResource;
use Eghamat24\DatabaseRepository\CustomMySqlQueries;
use Illuminate\Support\Collection;

class MakeResource extends BaseCommand
{
    use CustomMySqlQueries;

    private const OBJECT_NAME = 'Resource';

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

    public function handle(): void
    {
        $this->setArguments();
        $resourceName = $this->entityName . self::OBJECT_NAME;
        $resourceNamespace = config('repository.path.namespace.resources');
        $relativeResourcesPath = config('repository.path.relative.resources');

        $filenameWithPath = $relativeResourcesPath . $resourceName . '.php';

        $this->checkAndPrepare($filenameWithPath, $resourceName, $relativeResourcesPath, $resourceNamespace);

        $RepoCreator = $this->getResourceCreator($resourceNamespace, $resourceName);
        $baseContent = $this->generateBaseContent($RepoCreator, $filenameWithPath);

        $this->finalized($filenameWithPath, $resourceName, $baseContent);
    }

    /**
     * @param string $filenameWithPath
     * @param string $resourceName
     * @param mixed $relativeResourcesPath
     * @param mixed $resourceNamespace
     * @return void
     */
    private function checkAndPrepare(string $filenameWithPath, string $resourceName, mixed $relativeResourcesPath, mixed $resourceNamespace): void
    {
        $this->checkDelete($filenameWithPath, $resourceName, self::OBJECT_NAME);
        $this->checkDirectory($relativeResourcesPath);
        $this->checkClassExist($resourceNamespace, $resourceName, self::OBJECT_NAME);
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
     * @param mixed $resourceNamespace
     * @param string $resourceName
     * @return CreatorResource
     */
    private function getResourceCreator(mixed $resourceNamespace, string $resourceName): CreatorResource
    {
        $resourceStubsPath = __DIR__ . '/../../' . config('repository.path.stub.resources');

        return new CreatorResource($this->getColumnsOf($this->tableName),
            $this->tableName,
            $this->entityName,
            $this->entityNamespace,
            $resourceNamespace,
            $resourceName,
            $resourceStubsPath,
            $this->detectForeignKeys,
            $this->entityVariableName);
    }

    /**
     * @param CreatorResource $RepoCreator
     * @param string $filenameWithPath
     * @return string
     */
    private function generateBaseContent(CreatorResource $RepoCreator, string $filenameWithPath): string
    {
        $creator = new BaseCreator($RepoCreator);
        return $creator->createClass($filenameWithPath, $this);
    }

}
