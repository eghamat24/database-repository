<?php

namespace Nanvaie\DatabaseRepository\Creators;

use Illuminate\Support\Collection;
use Nanvaie\DatabaseRepository\CustomMySqlQueries;

interface IClassCreator
{
    public function getNameSpace(): string;
    public function createUses(): array;
    public function getClassName(): string;
    public function getExtendSection(): string;
    public function createAttributs(): array;
    public function createFunctions(): array;

}
