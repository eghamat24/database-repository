<?php

namespace Nanvaie\DatabaseRepository\Models\Repositories;

use Illuminate\Cache\CacheManager;

abstract class RedisRepository
{
    private CacheManager $cache;

    public function __construct()
    {
        $this->cache = app('cache');
    }

    protected function getCache(): CacheManager
    {
        return $this->cache;
    }
}
