<?php

namespace Nanvaie\DatabaseRepository\Models\Resource;

use Illuminate\Support\Collection;

interface IResource
{
    public function collectionToArray(Collection $entities): array;
}
