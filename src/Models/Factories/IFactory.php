<?php

namespace Nanvaie\DatabaseRepository\Models\Factories;

use Nanvaie\DatabaseRepository\Models\Entity\Entity;
use Illuminate\Support\Collection;
use stdClass;

interface IFactory
{
    /**
     * @param stdClass $entity
     * @return Entity
     */
    public function makeEntityFromStdClass(stdClass $entity): Entity;

    /**
     * @param Collection|array $entities
     * @return Collection
     */
    public function makeCollectionOfEntities(Collection|array $entities): Collection;
}
