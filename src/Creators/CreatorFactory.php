<?php

namespace Eghamat24\DatabaseRepository\Creators;

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

    public function createAttributes(): array
    {
        $setStub = file_get_contents($this->factoryStubsPath . 'set.stub');
        $sets = '';
        foreach ($this->columns as $_column) {
            $replacementTokens = [
                '{{ AttributeName }}' => Str::camel($_column->COLUMN_NAME),
                '{{ DatabaseAttributeName }}' => Str::snake($_column->COLUMN_NAME)
            ];

            $sets .= str_replace(array_keys($replacementTokens), array_values($replacementTokens), $setStub) . "\t\t";
        }

        return ['makeEntityFromStdClass' =>
            str_replace(['{{ Sets }}', '{{ EntityName }}', '{{ EntityVariableName }}'],
                [$sets, $this->entityName, $this->entityVariableName],
                $this->baseContent)
        ];
        return [];
    }

    public function createFunctions(): array
    {
        return [];
    }

    public function createUses(): array
    {
        return [
            "use $this->entityNamespace\\$this->entityName;",
            'use Eghamat24\DatabaseRepository\Models\Factories\Factory;',
            'use stdClass;'
        ];

    }

    public function getExtendSection(): string
    {
        return 'extends ' . self::PARENT_NAME;
    }

    public function getClassName(): string
    {
        return $this->factoryName;
    }
}
