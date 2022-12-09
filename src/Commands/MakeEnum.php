<?php

namespace Nanvaie\DatabaseRepository\Commands;

use Nanvaie\DatabaseRepository\CustomMySqlQueries;
use Illuminate\Console\Command;

class MakeEnum extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repository:make-enum {table_name}
    {--d|delete : Delete resource}
    {--f|force : Override/Delete enum}
    {--g|add-to-git : Add created file to git repository}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new enum(s).';

    use CustomMySqlQueries;

    /**
     * @param string $attributeStub
     * @param string $attributeName
     * @param string $attributeType
     * @return string
     */
    private function writeAttribute(string $attributeStub, string $attributeName, string $attributeString): string
    {
        return str_replace(['{{ AttributeName }}', '{{ AttributeString }}'],
            [$attributeName, $attributeString],
            $attributeStub);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $tableName = $this->argument('table_name');
        $enumNamespace = config('repository.path.namespace.enums');
        $relativeEntitiesPath = config('repository.path.relative.enums');
        $entityStubsPath = __DIR__ . '/../../' . config('repository.path.stub.enums');


        $columns = $this->getAllColumnsInTable($tableName);

        if ($columns->isEmpty()) {
            $this->alert("Couldn't retrieve columns from table \"$tableName\"! Perhaps table's name is misspelled.");
            die;
        }

        $enums = [];
        foreach ($columns as $_column) {
            if ($_column->DATA_TYPE == 'enum') {
                $enumClassName = studly_case(substr_replace($_column->TABLE_NAME, '', -1) . '_' . $_column->COLUMN_NAME);
                $enums[$enumClassName] = explode(',', str_replace(['enum(', '\'', ')'], ['', '', ''], $_column->COLUMN_TYPE));

                $filenameWithPath = $relativeEntitiesPath . $enumClassName.'.php';

                if (file_exists($filenameWithPath) && $this->option('delete')) {
                    unlink($filenameWithPath);
                    $this->info("Enum \"$enumClassName\" has been deleted.");
                    return 0;
                }
            }
        }

        // Create Attributes

        $baseContentStub = file_get_contents($entityStubsPath.'class.stub');
        $attributeStub = file_get_contents($entityStubsPath.'attribute.stub');

        foreach ($enums as $enumName => $enum) {
            $filenameWithPath = $relativeEntitiesPath . $enumName.'.php';


            if ( ! file_exists($relativeEntitiesPath) && ! mkdir($relativeEntitiesPath, 0775, true) && ! is_dir($relativeEntitiesPath)) {
                $this->alert("Directory \"$relativeEntitiesPath\" was not created");
                return 0;
            }

            if (class_exists($relativeEntitiesPath.'\\'.$enumName) && ! $this->option('force')) {
                $this->alert("Enum \"$enumName\" is already exist!");
                return 0;
            }

            $attributes = '';
            foreach ($enum as $_enum) {
                $attributes .= $this->writeAttribute(
                    $attributeStub,
                    strtoupper($_enum),
                    $_enum
                );
            }


            $baseContent = str_replace(['{{ EnumNamespace }}', '{{ EnumName }}', '{{ Attributes }}',],
                [$enumNamespace, $enumName, $attributes,],
                $baseContentStub);

            file_put_contents($filenameWithPath, $baseContent);

            if ($this->option('add-to-git')) {
                shell_exec('git add '.$filenameWithPath);
            }

            $this->info("Enum \"$enumName\" has been created.");
        }



        return 0;
    }
}
