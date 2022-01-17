<?php

namespace App\Models\Repositories;

use Illuminate\Cache\CacheManager;

abstract class  RedisRepository
{
    public function __construct()
    {
    }

    /**
     * @return CacheManager
     */
    public function newConnection()
    {
        return app('cache');
    }
}