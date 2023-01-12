<?php

namespace Nanvaie\DatabaseRepository\Models\Resources;

use Illuminate\Support\Collection;
use Nanvaie\DatabaseRepository\Models\Entity\Entity;

abstract class Resource implements IResource
{

    abstract public function toArray(Entity $entity): array;

    public function collectionToArray(Collection $entities): array
    {
        $entityArray = [];

        foreach ($entities as $_entity) {
            $entityArray[] = $this->toArray($_entity);
        }

        return $entityArray;
    }
}
