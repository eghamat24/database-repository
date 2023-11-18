<?php

namespace Eghamat24\DatabaseRepository\Models\Repositories\CacheStrategies;

trait QueryCacheStrategy
{
    /** @var string $cacheTag */
    private $cacheTag = '';

    /**
     * @param array $params
     * @return string
     */
    public function makeKey(array $params = [])
    {
        return md5(json_encode($params));
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
     * @return mixed
     */
    public function put(string $cacheKey, $data)
    {
        return $this->getCache()->tags($this->cacheTag)->forever($cacheKey, $data);
    }

    /**
     * @return mixed
     */
    public function clear()
    {
        return $this->getCache()->tags($this->cacheTag)->flush();
    }
}