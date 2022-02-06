<?php

namespace Changiz\DatabaseRepository\Commands;

use Illuminate\Console\Command;

class MakeAllRepository extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:make-all-repository {table_names*} {--k|foreign-keys : Detect foreign keys} {--d|delete : Delete resource} {--f|force : Override/Delete existing classes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create all classes necessary for repository.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tableNames = $this->argument('table_names');
        $force = $this->option('force');
        $delete = $this->option('delete');
        $detectForeignKeys = $this->option('foreign-keys');

        foreach ($tableNames as $_tableName) {
            $arguments = [
                'table_name' => $_tableName,
                '--foreign-keys' => $detectForeignKeys,
                '--delete' => $delete,
                '--force' => $force
            ];

            $this->call('command:make-entity', $arguments);
            $this->call('command:make-factory', ['table_name' => $_tableName, '--delete' => $delete, '--force' => $force]);
            $this->call('command:make-resource', $arguments);
            $this->call('command:make-interface-repository', $arguments);
            $this->call('command:make-mysql-repository', $arguments);
            $this->call('command:make-redis-repository', $arguments);
            $this->call('command:make-repository', ['table_name' => $_tableName, '--delete' => $delete, '--force' => $force]);
        }
    }
}
