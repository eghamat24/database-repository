<?php

namespace Eghamat24\DatabaseRepository\Commands;

use Illuminate\Support\Str;
use Eghamat24\DatabaseRepository\Creators\BaseCreator;
use Eghamat24\DatabaseRepository\Creators\CreatorEnum;
use Eghamat24\DatabaseRepository\CustomMySqlQueries;

class MakeEnum extends BaseCommand
{
    use CustomMySqlQueries;

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

    public function handle(): void
    {
        $this->setArguments();
        $columns = $this->getAllColumnsInTable($this->tableName);

        $this->checkEmpty($columns, $this->tableName);

        $enums = [];
        foreach ($columns as $_column) {
            if ($_column->DATA_TYPE == 'enum') {
                $enumClassName = Str::studly(Str::singular(ucfirst(Str::camel($_column->TABLE_NAME))) . '_' . $_column->COLUMN_NAME) . 'Enum';
                $enums[$enumClassName] = array_filter(explode(',', str_replace(['enum(', '\'', ')'], ['', '', ''], $_column->COLUMN_TYPE)));
                $filenameWithPath = $this->relativeEnumsPath . $enumClassName . '.php';
                $this->checkDelete($filenameWithPath, $enumClassName, 'Enum');
            }
        }

        $attributeStub = file_get_contents($this->enumStubPath . 'attribute.stub');

        foreach ($enums as $enumName => $enum) {
            $filenameWithPath = $this->relativeEnumsPath . $enumName . '.php';

            $this->checkDirectory($this->enumNamespace);
            $this->checkClassExist($this->relativeEnumsPath, $enumName, 'Enum');

            $enumCreator = new CreatorEnum($columns, $attributeStub, $enum, $enumName, $this->enumNamespace);
            $creator = new BaseCreator($enumCreator);
            $baseContent = $creator->createClass($filenameWithPath, $this);

            $this->finalized($filenameWithPath, $enumName, $baseContent);
        }
    }
}
