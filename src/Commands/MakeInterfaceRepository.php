<?php

namespace Eghamat24\DatabaseRepository\Commands;

use Eghamat24\DatabaseRepository\Models\Enums\DataTypeEnum;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Eghamat24\DatabaseRepository\CustomMySqlQueries;

class MakeInterfaceRepository extends BaseCommand
{
    use CustomMySqlQueries;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repository:make-interface-repository {table_name}
    {--k|foreign-keys : Detect foreign keys}
    {--d|delete : Delete resource}
    {--f|force : Override/Delete existing mysql repository}
    {--g|add-to-git : Add created file to git repository}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new interface for repository';


    public function handle(): void
    {
        $this->setArguments();
        $filenameWithPath = $this->relativeInterfacePath . $this->interfaceName . '.php';

        $this->checkAndPrepare($filenameWithPath);

        [
            'basedContent' => $baseContent,
            'getOneStub' => $getOneStub,
            'getAllStub' => $getAllStub,
            'createFunctionStub' => $createFunctionStub,
            'updateFunctionStub' => $updateFunctionStub,
            'deleteAndUndeleteStub' => $deleteAndUndeleteStub
        ] = $this->getStubContents($this->interfaceRepositoryStubsPath);

        $baseContent = $this->writeFunctionOnBaseContent(
            $baseContent, $this->writeGetOneFunction($getOneStub, 'id', DataTypeEnum::INTEGER_TYPE)
        );

        $baseContent = $this->writeFunctionOnBaseContent(
            $baseContent, $this->writeGetAllFunction($getAllStub, 'id', DataTypeEnum::INTEGER_TYPE)
        );

        $columns = $this->getColumnsOf($this->tableName);
        $indexes = $this->extractIndexes($this->tableName);
        $baseContent = $this->writeGetFunctionByIndexColumnOnBaseContent($indexes, $columns, $baseContent, $getOneStub, $getAllStub);
        $baseContent = $this->writeGetFunctionByForeignKeyOnBaseContent($baseContent, $getOneStub, $getAllStub);

        $allColumns = $columns->pluck('COLUMN_NAME')->toArray();
        $baseContent = $this->setTimestampsColumnOnBaseContent(
            $allColumns, $baseContent, $createFunctionStub, $updateFunctionStub, $deleteAndUndeleteStub
        );

        $baseContent = $this->replaceDataOnInterfaceContent($baseContent);

        $this->finalized($filenameWithPath, $this->entityName, $baseContent);
    }

    private function writeFunctionOnBaseContent($baseContent, string $writeFunction): string|array
    {
        return substr_replace($baseContent, $writeFunction, -2, 0);
    }

    private function writeFunction(string $stub, string $columnName, string $attributeType, array $placeHolders): array|string
    {
        $replaceValues = \array_map(function ($placeholder) use ($columnName, $attributeType) {

            return match ($placeholder) {
                '{{ FunctionName }}' => ucfirst(Str::camel($columnName)),
                '{{ ColumnName }}' => $columnName,
                '{{ AttributeType }}' => $attributeType,
                '{{ AttributeName }}' => Str::camel($columnName),
                '{{ FunctionNamePlural }}' => ucfirst(Str::plural(Str::camel($columnName))),
                '{{ AttributeNamePlural }}' => Str::plural(Str::camel($columnName)),
                default => $placeholder,
            };
        }, $placeHolders);

        return str_replace($placeHolders, $replaceValues, $stub);
    }

    private function writeGetOneFunction(string $getOneStub, string $columnName, string $attributeType): string
    {
        $placeHolders = ['{{ FunctionName }}', '{{ ColumnName }}', '{{ AttributeType }}', '{{ AttributeName }}'];
        return $this->writeFunction($getOneStub, $columnName, $attributeType, $placeHolders);
    }

    private function writeGetAllFunction(string $getAllStub, string $columnName, string $attributeType): string
    {
        $placeHolders = ['{{ FunctionNamePlural }}', '{{ AttributeType }}', '{{ AttributeNamePlural }}'];
        return $this->writeFunction($getAllStub, $columnName, $attributeType, $placeHolders);
    }

    private function getColumnsOf(string $tableName): Collection
    {
        $columns = $this->getAllColumnsInTable($tableName);
        $this->checkEmpty($columns, $tableName);

        return $columns;
    }

    private function checkAndPrepare(string $filenameWithPath): void
    {
        $this->checkDelete($filenameWithPath, $this->interfaceName, 'Interface');
        $this->checkDirectory($this->relativeInterfacePath);
        $this->checkClassExist($this->repositoryNamespace, $this->interfaceName, 'Interface');
    }

    private function setTimestampsColumnOnBaseContent(
        array        $allColumns,
        array|string $baseContent,
        bool|string  $createFunctionStub,
        bool|string  $updateFunctionStub,
        bool|string  $deleteAndUndeleteStub): string|array
    {
        if (in_array('created_at', $allColumns, true)) {
            $baseContent = substr_replace($baseContent, $createFunctionStub, -2, 0);
        }

        if (in_array('updated_at', $allColumns, true)) {
            $baseContent = substr_replace($baseContent, $updateFunctionStub, -2, 0);
        }

        if (in_array('deleted_at', $allColumns, true)) {
            $baseContent = substr_replace($baseContent, $deleteAndUndeleteStub, -2, 0);
        }

        return $baseContent;
    }

    private function getStubContents(string $basePath): array
    {
        $stubs = [
            'basedContent' => 'class.stub',
            'getOneStub' => 'getOneBy.stub',
            'getAllStub' => 'getAllBy.stub',
            'createFunctionStub' => 'create.stub',
            'updateFunctionStub' => 'update.stub',
            'deleteAndUndeleteStub' => 'deleteAndUndelete.stub',
        ];

        $stubsContent = [];

        foreach ($stubs as $name => $endWith) {
            $stubsContent[$name] = file_get_contents($basePath . $endWith);
        }

        return $stubsContent;
    }

    private function replaceDataOnInterfaceContent(array|string $baseContent): string|array
    {
        $placeHolders = [
            '{{ EntityName }}' => $this->entityName,
            '{{ EntityNamespace }}' => $this->entityNamespace,
            '{{ EntityVariableName }}' => $this->entityVariableName,
            '{{ InterfaceRepositoryName }}' => $this->interfaceName,
            '{{ RepositoryNamespace }}' => $this->repositoryNamespace
        ];

        return \str_replace(\array_keys($placeHolders), \array_values($placeHolders), $baseContent);
    }

    private function writeGetFunctionByIndexColumnOnBaseContent(Collection $indexes, Collection $columns, mixed $baseContent, $getOneStub, $getAllStub): mixed
    {
        foreach ($indexes as $index) {
            $columnInfo = collect($columns)->where('COLUMN_NAME', $index->COLUMN_NAME)->first();

            $baseContent = $this->writeFunctionOnBaseContent($baseContent,
                $this->writeGetOneFunction(
                    $getOneStub, $index->COLUMN_NAME, $this->getDataType($columnInfo->COLUMN_TYPE, $columnInfo->DATA_TYPE)
                )
            );

            if ($index->Non_unique == 1) {

                $baseContent = $this->writeFunctionOnBaseContent($baseContent,
                    $this->writeGetOneFunction(
                        $getAllStub, $index->COLUMN_NAME, $this->getDataType($columnInfo->COLUMN_TYPE, $columnInfo->DATA_TYPE)
                    )
                );
            }
        }

        return $baseContent;
    }

    public function writeGetFunctionByForeignKeyOnBaseContent(array|string $baseContent, $getOneStub, $getAllStub): string|array
    {
        if (empty($this->detectForeignKeys)) {
            return $baseContent;
        }

        $foreignKeys = $this->extractForeignKeys($this->tableName);

        foreach ($foreignKeys as $_foreignKey) {

            $baseContent = $this->writeFunctionOnBaseContent(
                $baseContent, $this->writeGetOneFunction($getOneStub, $_foreignKey->COLUMN_NAME, $this->entityName)
            );

            $baseContent = $this->writeFunctionOnBaseContent(
                $baseContent, $this->writeGetAllFunction($getAllStub, $_foreignKey->COLUMN_NAME, $this->entityName)
            );
        }

        return $baseContent;
    }
}
