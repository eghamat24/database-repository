<?php

namespace App\Http\Resources;

use Illuminate\Support\Collection;

abstract class Resource implements IResource
{
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
