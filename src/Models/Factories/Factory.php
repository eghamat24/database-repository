<?php

namespace Nanvaie\DatabaseRepository\Models\Factories;

use Nanvaie\DatabaseRepository\Models\Entity\Entity;
use Illuminate\Support\Collection;
use stdClass;

abstract class Factory implements IFactory
{
    abstract public function makeEntityFromStdClass(stdClass $entity): Entity;

    public function makeCollectionOfEntities(Collection|array $entities): Collection
    {
        $entityCollection = collect();

        foreach ($entities as $_entity) {
            if (is_array($_entity)) {
                $_entity = (object)$_entity;
            }
            $entityCollection->push($this->makeEntityFromStdClass($_entity));
        }

        return $entityCollection;
    }
}
