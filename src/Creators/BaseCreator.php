<?php

namespace Nanvaie\DatabaseRepository\Creators;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use function Nanvaie\DatabaseRepository\Commands\config;

class BaseCreator
{
    private $creator;

    public function __construct(IClassCreator $creator)
    {
        $this->creator = $creator;
    }

    public function createClass():string
    {
//        Create Attributes
        $attributesArray = $this->creator->createAttributs(
            $this->creator->columns,
            $this->creator->attributeStub,
            $this->creator->detectForeignKeys,
            $this->creator->tableName);
        $attributes = implode('',array_column($attributesArray, '1'));


//      Create Setters and Getters
        $settersAndGettersArray = $this->creator->createFunctions(
            $this->creator->columns,
            $this->creator->accessorsStub,
            $this->creator->detectForeignKeys,
            $this->creator->tableName);
        $settersAndGetters = implode('',array_column($settersAndGettersArray, '1'));

        $this->creator->baseContent = str_replace(['{{ EntityNamespace }}', '{{ EntityName }}', '{{ Attributes }}', '{{ SettersAndGetters }}'],
            [
                $this->creator->entityNamespace,
                $this->creator->entityName,
                $attributes,
                $settersAndGetters
            ],
            $this->creator->baseContent);

        return $this->creator->baseContent;
    }





}
