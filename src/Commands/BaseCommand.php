<?php

namespace Nanvaie\DatabaseRepository\Commands;
use Illuminate\Support\Collection;
use Illuminate\Console\Command;
class BaseCommand extends Command
{

    public function checkDelete(string $filenameWithPath,string $entityName){
        if (file_exists($filenameWithPath) && $this->option('delete')) {
            unlink($filenameWithPath);
            $this->info("Entity \"$entityName\" has been deleted.");
            return 0;
        }
    }
    public function checkDirectory(string $relativeEntitiesPath,string $entityName){
        if ( ! file_exists($relativeEntitiesPath) && ! mkdir($relativeEntitiesPath, 0775, true) && ! is_dir($relativeEntitiesPath)) {
            $this->alert("Directory \"$relativeEntitiesPath\" was not created");
            return 0;
        }
    }
    public function checkClassExist(string $relativeEntitiesPath, string $entityName){
        if (class_exists($relativeEntitiesPath.'\\'.$entityName) && ! $this->option('force')) {
            $this->alert("Entity \"$entityName\" is already exist!");
            return 0;
        }
    }

    public function finalized(string $filenameWithPath,string $entityName, string $baseContent){
        file_put_contents($filenameWithPath, $baseContent);
        if ($this->option('add-to-git')) {
            shell_exec('git add '.$filenameWithPath);
        }

        $this->info("Entity \"$entityName\" has been created.");
    }

    public function checkEmpty(Collection $columns,string $tableName){
        if ($columns->isEmpty()) {
            $this->alert("Couldn't retrieve columns from table \"$tableName\"! Perhaps table's name is misspelled.");
            die;
        }
    }
}
