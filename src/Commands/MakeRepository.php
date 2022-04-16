<?php

namespace Nanvaie\DatabaseRepository\Commands;

use Illuminate\Console\Command;

class MakeRepository extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:make-repository {table_name}
    {--d|delete : Delete resource}
    {--f|force : Override/Delete existing repository class}
    {--g|add-to-git : Add created file to git repository}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new repository';

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
     *
     * @return int
     */
    public function handle(): int
    {
        $tableName = $this->argument('table_name');
        $entityName = str_singular(ucfirst(camel_case($tableName)));
        $mysqlRepositoryName = "MySql$entityName" . "Repository";
        $repository = $entityName . "Repository";
        $repositoryNamespace = config('repository.path.namespace.repository');
        $relativeRepositoryPath = config('repository.path.relative.repository') . "\\$entityName";

        if ($this->option('delete')) {
            unlink("$relativeRepositoryPath/$repository.php");
            $this->info("Repository \"$repository\" has been deleted.");
            return 0;
        }

        if ( ! file_exists($relativeRepositoryPath) && ! mkdir($relativeRepositoryPath, 775, true) && ! is_dir($relativeRepositoryPath)) {
            $this->alert("Directory \"$relativeRepositoryPath\" was not created");
            return 0;
        }

        if (class_exists("$relativeRepositoryPath\\$repository") && !$this->option('force')) {
            $this->alert("Repository $repository is already exist!");
            die;
        }

        // Initialize Repository
        $repositoryContent = "<?php\n\nnamespace $repositoryNamespace\\$entityName;\n\n";
        $repositoryContent .= "class $repository extends $mysqlRepositoryName\n{\n\n}";

        file_put_contents("$relativeRepositoryPath/$repository.php", $repositoryContent);

        if ($this->option('add-to-git')) {
            shell_exec("git add $relativeRepositoryPath/$repository.php");
        }

        $this->info("Repository \"$repository\" has been created.");

        return 0;
    }
}
