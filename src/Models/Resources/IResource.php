<?php

namespace Eghamat24\DatabaseRepository\Models\Resources;

use Illuminate\Support\Collection;
use Eghamat24\DatabaseRepository\Models\Entity\Entity;

interface IResource
{
    public function toArray(Entity $entity): array;

    public function collectionToArray(Collection $entities): array;
}
