<?php

namespace Eghamat24\DatabaseRepository\Models\Repositories\CacheStrategies;

use Illuminate\Support\Collection;

trait SingleKeyCacheStrategy
{
    /** @var string $cacheKey */
    private $cacheKey = '';

    /**
     * @param array|Collection $entities
     */
    public function put($entities)
    {
        $this->getCache()->forever($this->cacheKey, $entities);
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return $this->getCache()->get($this->cacheKey);
    }

    /**
     * @return mixed
     */
    public function clear()
    {
        return $this->getCache()->forget($this->cacheKey);
    }
}