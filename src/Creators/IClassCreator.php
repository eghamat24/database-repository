<?php

namespace Eghamat24\DatabaseRepository\Creators;

use Illuminate\Support\Collection;
use Eghamat24\DatabaseRepository\CustomMySqlQueries;

interface IClassCreator
{
    public function getNameSpace(): string;
    public function createUses(): array;
    public function getClassName(): string;
    public function getExtendSection(): string;
    public function createAttributs(): array;
    public function createFunctions(): array;

}
