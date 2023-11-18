<?php

namespace Eghamat24\DatabaseRepository\Models\Factories;

use Eghamat24\DatabaseRepository\Models\Entity\Entity;
use Illuminate\Support\Collection;
use stdClass;

interface IFactory
{
    public function makeEntityFromStdClass(stdClass $entity): Entity;

    public function makeCollectionOfEntities(Collection|array $entities): Collection;
}
