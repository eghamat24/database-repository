<?php

namespace Changiz\DatabaseRepository\Models\Resource;

use Illuminate\Support\Collection;

interface IResource
{
    public function collectionToArray(Collection $entities): array;
}
