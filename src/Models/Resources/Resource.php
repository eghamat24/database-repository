<?php

namespace Nanvaie\DatabaseRepository\Models\Resources;

use Illuminate\Support\Collection;
use Nanvaie\DatabaseRepository\Models\Entity\Entity;

abstract class Resource implements IResource
{
    /**
     * @param Entity $entity
     * @return array
     */
    abstract public function toArray(Entity $entity): array;

    /**
     * @param Collection $entities
     * @return array
     */
    public function collectionToArray(Collection $entities): array
    {
        $entityArray = [];

        foreach ($entities as $_entity) {
            $entityArray[] = $this->toArray($_entity);
        }

        return $entityArray;
    }
}
