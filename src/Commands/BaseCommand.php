<?php

namespace Eghamat24\DatabaseRepository\Commands;

use Illuminate\Support\Collection;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

abstract class BaseCommand extends Command
{
    public string $selectedDb;
    public string $tableName;
    public string $detectForeignKeys;
    public string $entityName;
    public string $entityNamespace;
    public string $relativeEntitiesPath;
    public string $entityStubsPath;
    public string $enumNamespace;
    public string $relativeEnumsPath;
    public string $enumStubPath;

    public string $entityVariableName;
    public string $factoryName;
    public string $factoryNamespace;
    public string $relativeFactoriesPath;
    public string $factoryStubsPath;
    public string $interfaceName;
    public string $repositoryNamespace;
    public string $relativeInterfacePath;
    public string $interfaceRepositoryStubsPath;

    public string $mysqlRepositoryName;
    public string $relativeMysqlRepositoryPath;
    public string $mysqlRepositoryStubsPath;

    public string $strategyName;

    public function setArguments()
    {
        $this->selectedDb = $this->hasArgument('selected_db') && $this->argument('selected_db') ? $this->argument('selected_db') : config('repository.default_db');
        $this->tableName = $this->argument('table_name');
        if ($this->hasOption('foreign-keys')) $this->detectForeignKeys = $this->option('foreign-keys');
        $this->entityName = Str::singular(ucfirst(Str::camel($this->tableName)));
        $this->entityNamespace = config('repository.path.namespace.entities');
        $this->relativeEntitiesPath = config('repository.path.relative.entities');
        $this->entityStubsPath = __DIR__ . '/../../' . config('repository.path.stub.entities');

        $this->enumNamespace = config('repository.path.namespace.enums');
        $this->relativeEnumsPath = config('repository.path.relative.enums');
        $this->enumStubPath = __DIR__ . '/../../' . config('repository.path.stub.enums');

        $this->entityVariableName = Str::camel($this->entityName);
        $this->factoryName = $this->entityName . 'Factory';
        $this->factoryNamespace = config('repository.path.namespace.factories');
        $this->relativeFactoriesPath = config('repository.path.relative.factories');
        $this->factoryStubsPath = __DIR__ . '/../../' . config('repository.path.stub.factories');

        $this->interfaceName = "I$this->entityName" . "Repository";
        $this->repositoryNamespace = config('repository.path.namespace.repositories');
        $this->relativeInterfacePath = config('repository.path.relative.repositories') . "$this->entityName" . DIRECTORY_SEPARATOR;
        $this->interfaceRepositoryStubsPath = __DIR__ . '/../../' . config('repository.path.stub.repositories.interface');

        $this->mysqlRepositoryName = 'MySql' . $this->entityName . 'Repository';
        $this->relativeMysqlRepositoryPath = config('repository.path.relative.repositories') . "$this->entityName" . DIRECTORY_SEPARATOR;
        $this->mysqlRepositoryStubsPath = __DIR__ . '/../../' . config('repository.path.stub.repositories.mysql');
        if ($this->hasArgument('strategy')) {
            $this->strategyName = $this->argument('strategy');
        }
    }

    public function checkDelete(string $filenameWithPath, string $entityName, string $objectName): void
    {
        if (file_exists($filenameWithPath) && $this->option('delete')) {
            \unlink($filenameWithPath);
            $this->info("$objectName '$entityName' has been deleted.");
        }
    }

    public function checkDirectory(string $relativeEntitiesPath): void
    {
        if (!file_exists($relativeEntitiesPath) && !mkdir($relativeEntitiesPath, 0775, true) && !is_dir($relativeEntitiesPath)) {
            $this->alert("Directory \"$relativeEntitiesPath\" was not created");
            exit;
        }
    }

    public function checkClassExist(string $nameSpace, string $entityName, string $objectName): void
    {
        if (class_exists($nameSpace . '\\' . $entityName) && !$this->option('force')) {
            $this->alert("$objectName \"$entityName\" is already exist!");
            exit;
        }
    }

    public function finalized(string $filenameWithPath, string $entityName, string $baseContent): void
    {
        file_put_contents($filenameWithPath, $baseContent);
        if ($this->option('add-to-git')) {
            shell_exec('git add ' . $filenameWithPath);
        }

        $this->info("\"$entityName\" has been created.");
    }

    public function checkEmpty(Collection $columns, string $tableName): void
    {
        if ($columns->isEmpty()) {
            $this->alert("Couldn't retrieve columns from table \"$tableName\"! Perhaps table's name is misspelled.");
            exit;
        }
    }

    public function setChoice($choice): void
    {
        \config(['replacement.choice' => $choice]);
    }

    public function getChoice(): null|string
    {
        return \config('replacement.choice');
    }

    public function checkStrategyName()
    {
        $strategyNames = [
            'ClearableTemporaryCacheStrategy',
            'QueryCacheStrategy',
            'SingleKeyCacheStrategy',
            'TemporaryCacheStrategy'
        ];

        if (!in_array($this->argument('strategy'), $strategyNames)) {
            $this->alert('This pattern strategy does not exist !!! ');
            exit;
        }
    }

    public function checkDatabasesExist()
    {
        $entityName = Str::singular(ucfirst(Str::camel($this->argument('table_name'))));
        $mysql = config('repository.path.relative.repositories') . DIRECTORY_SEPARATOR . $entityName . DIRECTORY_SEPARATOR . 'MySql' . $entityName . 'Repository.php';
        $redis = config('repository.path.relative.repositories') . DIRECTORY_SEPARATOR . $entityName . DIRECTORY_SEPARATOR . 'Redis' . $entityName . 'Repository.php';

        if (!(file_exists($mysql) || file_exists($redis))) {
            $this->alert("First create the class databases!!!");
            exit;
        }
    }

}
