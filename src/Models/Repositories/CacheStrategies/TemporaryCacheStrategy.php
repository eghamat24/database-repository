<?php

namespace Eghamat24\DatabaseRepository\Models\Repositories\CacheStrategies;

use DateInterval;
use DateTimeInterface;

trait TemporaryCacheStrategy
{
    private string $cacheTag = '';

    /**
     * @param array $params
     * @return string
     */
    public function makeKey(array $params = []): string
    {
        return md5(json_encode($params));
    }

    /**
     * @param string $cacheKey
     * @return mixed
     */
    public function get(string $cacheKey): mixed
    {
        return $this->getCache()->tags($this->cacheTag)->get($cacheKey);
    }

    /**
     * @param string $cacheKey
     * @param mixed $data
     * @param DateInterval|DateTimeInterface|int $ttl
     * @return bool
     */
    public function put(string $cacheKey, mixed $data, DateInterval|DateTimeInterface|int $ttl): bool
    {
        return $this->getCache()->tags($this->cacheTag)->put($cacheKey, $data, $ttl);
    }
}
