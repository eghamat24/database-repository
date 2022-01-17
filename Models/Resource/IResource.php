<?php

namespace App\Http\Resources;

use Illuminate\Support\Collection;

interface IResource
{
    public function collectionToArray(Collection $entities): array;
}
