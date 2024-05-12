<?php

namespace Eghamat24\DatabaseRepository\Commands;

use Eghamat24\DatabaseRepository\CustomMySqlQueries;

class MakeAll extends BaseCommand
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
        $strategyNames = [
            'ClearableTemporaryCacheStrategy',
            'QueryCacheStrategy',
            'SingleKeyCacheStrategy',
            'TemporaryCacheStrategy'
        ];

        $strategy = $this->option('strategy_name');

        if ($strategy !== null && in_array($strategy, $strategyNames) === false) {
            $this->alert('This pattern strategy does not exist !!! ');
            exit;
        }

        $selectedDb = $this->option('selected_db') ?: config('repository.default_db');

        $force = $this->option('force');
        $delete = $this->option('delete');
        $detectForeignKeys = $this->option('foreign-keys');
        $addToGit = $this->option('add-to-git');

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

            $this->runCommandsWithArguments($arguments, $strategy, $selectedDb);
        }
    }

    /**
     * @param array $arguments
     * @param bool|array|string|null $strategy
     * @param mixed $selectedDb
     * @return void
     */
    private function runCommandsWithArguments(array $arguments, bool|array|string|null $strategy, mixed $selectedDb): void
    {
        $commands = [
            'repository:make-entity' => $arguments,
            'repository:make-enum' => array_diff_key($arguments, ['--foreign-keys' => null]),
            'repository:make-factory' => array_diff_key($arguments, ['--foreign-keys' => null]),
            'repository:make-resource' => $arguments,
            'repository:make-interface-repository' => $arguments,
            'repository:make-mysql-repository' => $arguments,
            'repository:make-repository' => $arguments + ['strategy' => $strategy, 'selected_db' => $selectedDb]
        ];

        if ($strategy !== null) {
            $commands['repository:make-redis-repository'] = $arguments + ['strategy' => $strategy];
        }

        foreach ($commands as $command => $args) {
            $this->call($command, $args);
        }
    }
}
