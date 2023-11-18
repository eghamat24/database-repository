<?php

namespace Eghamat24\DatabaseRepository\Models\Repositories\CacheStrategies;

trait ClearableTemporaryCacheStrategy
{
    /** @var string $cacheTag */
    private $cacheTag = '';

    /**
     * @param array $params
     * @return string
     */
    public function makeKey(array $params = [])
    {
        return md5(serialize($params));
    }

    /**
     * @param string $cacheKey
     * @return mixed
     */
    public function get(string $cacheKey)
    {
        return $this->getCache()->tags($this->cacheTag)->get($cacheKey);
    }

    /**
     * @param string $cacheKey
     * @param mixed $data
     * @param $time
     * @return mixed
     */
    public function put(string $cacheKey, $data, $seconds)
    {
        $this->getCache()->tags($this->cacheTag)->put($cacheKey, $data, $seconds);
    }

    /**
     * @return mixed
     */
    public function clear($cacheKey)
    {
        return $this->getCache()->tags($this->cacheTag)->forget($cacheKey);
    }
}
