<?php

namespace Eghamat24\DatabaseRepository\Commands;

use Illuminate\Console\Command;
use Eghamat24\DatabaseRepository\CustomMySqlQueries;
use phpDocumentor\Reflection\PseudoTypes\NonEmptyLowercaseString;

class MakeAll extends Command
{
    use CustomMySqlQueries;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repository:make-all
    {--selected_db= : Main database}
    {--table_names= : Table names, separate names with comma}
    {--strategy_name= : strategy name}
    {--k|foreign-keys : Detect foreign keys}
    {--d|delete : Delete resource}
    {--f|force : Override/Delete existing classes}
    {--g|add-to-git : Add created file to git repository}
    {--a|all-tables : Add created file to git repository}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create all classes necessary for repository.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $strategyNames = array("ClearableTemporaryCacheStrategy", "QueryCacheStrategy", "SingleKeyCacheStrategy", "TemporaryCacheStrategy");
        if (!in_array($this->option('strategy_name'), $strategyNames)) {
            $this->alert("This pattern strategy does not exist !!! ");
            exit;
        }

        $this->selectedDb = $this->hasOption('selected_db') && $this->option('selected_db') ? $this->option('selected_db') : config('repository.default_db');
        $force = $this->option('force');
        $delete = $this->option('delete');
        $detectForeignKeys = $this->option('foreign-keys');
        $addToGit = $this->option('add-to-git');
        $strategy = $this->option('strategy_name');
        if ($this->option('all-tables')) {
            $tableNames = $this->getAllTableNames()->pluck('TABLE_NAME');
        } else if ($this->option('table_names')) {
            $tableNames = explode(',', $this->option('table_names'));
        } else {
            $this->alert("Please choose one of two options '--all-tables' or '--table_names=' ");
            die;
        }

        foreach ($tableNames as $_tableName) {
            $arguments = [
                'table_name' => $_tableName,
                '--foreign-keys' => $detectForeignKeys,
                '--delete' => $delete,
                '--force' => $force,
                '--add-to-git' => $addToGit
            ];
            $this->call('repository:make-entity', $arguments);
            $this->call('repository:make-enum', ['table_name' => $_tableName, '--delete' => $delete, '--force' => $force, '--add-to-git' => $addToGit]);
            $this->call('repository:make-factory', ['table_name' => $_tableName, '--delete' => $delete, '--force' => $force, '--add-to-git' => $addToGit]);
            $this->call('repository:make-resource', $arguments);
            $this->call('repository:make-interface-repository', $arguments);
            $this->call('repository:make-mysql-repository', $arguments);
            $this->call('repository:make-redis-repository', [...$arguments, 'strategy' => $strategy]);
            $this->call('repository:make-repository', [...$arguments, 'strategy' => $strategy, 'selected_db' => $this->selectedDb]);
        }
    }
}
