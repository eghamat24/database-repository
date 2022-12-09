<?php

namespace Nanvaie\DatabaseRepository\Commands;

use Illuminate\Console\Command;

class MakeAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repository:make-all {table_names*}
    {--k|foreign-keys : Detect foreign keys}
    {--d|delete : Delete resource}
    {--f|force : Override/Delete existing classes}
    {--g|add-to-git : Add created file to git repository}';

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
        $tableNames = $this->argument('table_names');
        $force = $this->option('force');
        $delete = $this->option('delete');
        $detectForeignKeys = $this->option('foreign-keys');
        $addToGit = $this->option('add-to-git');

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
            $this->call('repository:make-redis-repository', $arguments);
            $this->call('repository:make-repository', $arguments);
        }
    }
}
