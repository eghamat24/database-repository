<?php

namespace Nanvaie\DatabaseRepository\Creators;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CreatorFactory implements IClassCreator
{
    protected const PARENT_NAME = 'Factory';

    public function __construct(
        public Collection $columns,
        public string     $entityName,
        public string     $entityNamespace,
        public string     $factoryStubsPath,
        public string     $factoryNamespace,
        public string     $entityVariableName,
        public string     $factoryName,
        public string     $baseContent)
    {
    }

    public function getNameSpace(): string
    {
        return $this->factoryNamespace;
    }

    public function createAttributs(): array
    {
        // TODO: Implement createAttributs() method.
        return [];
    }

    public function createFunctions(): array
    {
        $setterStub = file_get_contents($this->factoryStubsPath . 'setter.stub');
        $setterFunctions = '';
        foreach ($this->columns as $_column) {
            $setterFunctions .= $this->writeSetter($setterStub, $_column->COLUMN_NAME);
        }
        return ['makeEntityFromStdClass' =>
            str_replace(['{{ SetterFunctions }}', '{{ EntityName }}', '{{ EntityVariableName }}'],
                [$setterFunctions, $this->entityName, $this->entityVariableName],
                $this->baseContent)
        ];
    }

    public function createUses(): array
    {
        return [
            "use $this->entityNamespace\\$this->entityName;",
            "use Nanvaie\DatabaseRepository\Models\Factories\Factory;",
            "use stdClass;"
        ];

    }

    public function getExtendSection(): string
    {
        return 'extends ' . self::PARENT_NAME;
    }

    public function writeSetter(string $setterStub, string $columnName): string
    {
        return str_replace(['{{ SetterName }}', '{{ AttributeName }}'],
            [ucfirst($columnName), Str::snake($columnName)],
            $setterStub);
    }

    public function getClassName(): string
    {
        return $this->factoryName;
    }
}
