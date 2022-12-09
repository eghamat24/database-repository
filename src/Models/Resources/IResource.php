<?php

namespace Nanvaie\DatabaseRepository\Models\Resources;

use Illuminate\Support\Collection;
use Nanvaie\DatabaseRepository\Models\Entity\Entity;

interface IResource
{
    public function toArray(Entity $entity): array;

    public function collectionToArray(Collection $entities): array;
}
