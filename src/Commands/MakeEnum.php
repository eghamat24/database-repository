<?php

namespace Eghamat24\DatabaseRepository\Commands;

use Illuminate\Support\Collection;
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

        $enums = $this->extractEnumsFromColumns($columns);

        $attributeStub = file_get_contents($this->enumStubPath . 'attribute.stub');

        foreach ($enums as $enumName => $enum) {
            $filenameWithPath = $this->relativeEnumsPath . $enumName . '.php';

            $this->checkDirectory($this->enumNamespace);
            $this->checkClassExist($this->relativeEnumsPath, $enumName, 'Enum');

            $baseContent = $this->getBaseCreator($columns, $attributeStub, $enum, $enumName)
                ->createClass($filenameWithPath, $this);

            $this->finalized($filenameWithPath, $enumName, $baseContent);
        }
    }


    /**
     * @param Collection $columns
     * @return array
     */
    public function extractEnumsFromColumns(Collection $columns): array
    {
        $enums = [];
        foreach ($columns as $_column) {

            if ($_column->DATA_TYPE !== 'enum') {
                continue;
            }

            $enumClassName = $this->getEnumClassName($_column);
            $enums[$enumClassName] = $this->extractEnumValues($_column->COLUMN_TYPE);

            $this->checkDelete(
                $this->relativeEnumsPath . $enumClassName . '.php',
                $enumClassName,
                'Enum'
            );
        }

        return $enums;
    }

    private function getEnumClassName(mixed $_column): string
    {
        $tableName = ucfirst(Str::camel($_column->TABLE_NAME));
        $columnName = $_column->COLUMN_NAME;

        return Str::studly(Str::singular($tableName) . '_' . $columnName) . 'Enum';
    }

    private function extractEnumValues($columnType): array
    {
        $items = explode(',', str_replace(['enum(', '\'', ')'], ['', '', ''], $columnType));

        return array_filter($items);
    }

    /**
     * @param Collection $columns
     * @param bool|string $attributeStub
     * @param mixed $enum
     * @param int|string $enumName
     * @return BaseCreator
     */
    private function getBaseCreator(Collection $columns, bool|string $attributeStub, mixed $enum, int|string $enumName): BaseCreator
    {
        $enumCreator = new CreatorEnum($columns, $attributeStub, $enum, $enumName, $this->enumNamespace);

        return new BaseCreator($enumCreator);
    }
}
