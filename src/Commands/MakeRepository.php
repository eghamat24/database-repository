<?php

namespace Nanvaie\DatabaseRepository\Commands;

//use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Nanvaie\DatabaseRepository\Creators\BaseCreator;
use Nanvaie\DatabaseRepository\Creators\CreatorRepository;
use Nanvaie\DatabaseRepository\CustomMySqlQueries;

class MakeRepository extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repository:make-repository {table_name}
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

    use CustomMySqlQueries;

    private function writeFunction(string $functionStub, string $functionName, string $columnName, string $attributeType): string
    {
        if ($functionName === 'getOneBy') {
            $functionReturnType = 'null|{{ EntityName }}';
            $functionName .= ucfirst(Str::camel($columnName));
            $columnName = Str::camel($columnName);
        } elseif ($functionName === 'getAllBy') {
            $functionReturnType = 'Collection';
            $functionName .= ucfirst(Str::plural(Str::camel($columnName)));
            $columnName = Str::plural(Str::camel($columnName));
        } elseif ($functionName === 'create') {
            $functionReturnType = $attributeType;
        } elseif (in_array($functionName, ['update', 'remove', 'restore'])) {
            $functionReturnType = 'int';
        }

        return str_replace(['{{ FunctionName }}', '{{ AttributeType }}', '{{ AttributeName }}', '{{ FunctionReturnType }}'],
            [$functionName, $attributeType, Str::camel($columnName), $functionReturnType],
            $functionStub);
    }

    private function writeSqlAttribute(string $attributeStub, string $sqlRepositoryVariable): string
    {
        return str_replace(['{{ SqlRepositoryVariable }}'],
            [$sqlRepositoryVariable],
            $attributeStub);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): void
    {
        $this->setArguments();
        $repositoryName = $this->entityName.'Repository';
        $sqlRepositoryName = 'MySql'.$this->entityName.'Repository';
        $sqlRepositoryVariable = 'mysqlRepository';
        $relativeRepositoryPath = config('repository.path.relative.repositories') . "$this->entityName" . DIRECTORY_SEPARATOR;
        $repositoryStubsPath = __DIR__ . '/../../' . config('repository.path.stub.repositories.base');
        $filenameWithPath = $relativeRepositoryPath . $repositoryName . '.php';

        $this->checkDelete($filenameWithPath,$repositoryName,"Repository");
        $this->checkDirectory($relativeRepositoryPath);
        $this->checkClassExist($this->repositoryNamespace,$repositoryName,"Repository");

        $columns = $this->getAllColumnsInTable($this->tableName);
        $this->checkEmpty($columns,$this->tableName);

        $RepoCreator = new CreatorRepository(
            $columns,
            $sqlRepositoryVariable,
            $sqlRepositoryName,
            $repositoryStubsPath,
            $this->detectForeignKeys,
            $this->tableName,
            $this->entityVariableName,
            $this->entityName,
            $this->entityNamespace,
            $repositoryName,
            $this->interfaceName,
            $this->repositoryNamespace
        );
        $creator = new BaseCreator($RepoCreator);
        $baseContent = $creator->createClass($filenameWithPath,$this);

        $this->finalized($filenameWithPath, $repositoryName, $baseContent);
    }
}
