<?php

namespace Eghamat24\DatabaseRepository\Models\Repositories;

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
    /**
     * @param Collection $entities
     * @param Filter[]|Collection $filters
     * @return Collection
     */
    protected function processFilterWithCollection($entities, $filters)
    {
        foreach ($filters as $filter) {
            $columnName = camel_case($filter->getColumnName());
            $value = $filter->getValue();

            switch ($filter->getOperand()) {
                case 'IsEqualTo':
                    $entities = $entities->where($columnName, '=', $value);
                    break;
                case 'IsEqualToOrNull':
                    $entities = $entities->filter(function ($entity, $key) use ($columnName, $value) {
                        return ($entity->$columnName == $value || empty($entity->$columnName));
                    });
                    break;
                case 'IsNull':
                    $entities = $entities->whereNull($columnName);
                    break;
                case 'IsNotEqualTo':
                    $entities = $entities->where($columnName, '<>', $value);
                    break;
                case 'IsNotNull':
                    $entities = $entities->whereNotNull($columnName);
                    break;
                case 'StartWith':
                    $entities = $entities->filter(function ($entity) use ($columnName, $value) {
                        return false !== Str::startsWith($entity->$columnName, $value);
                    });
                    break;
                case 'DoesNotContains':
                    $entities = $entities->filter(function ($entity) use ($columnName, $value) {
                        return false === Str::contains($entity->$columnName, $value);
                    });
                    break;
                case 'Contains':
                    $entities = $entities->filter(function ($entity) use ($columnName, $value) {
                        return false !== Str::contains($entity->$columnName, $value);
                    });
                    break;
                case 'EndsWith':
                    $entities = $entities->filter(function ($entity) use ($columnName, $value) {
                        return false !== Str::endsWith($entity->$columnName, $value);
                    });
                    break;
                case 'In':
                    $entities = $entities->whereIn($columnName, $value);
                    break;
                case 'NotIn':
                    $entities = $entities->whereNotIn($columnName, $value);
                    break;
                case 'Between':
                    $entities = $entities->whereBetween($columnName, $value);
                    break;
                case 'IsGreaterThanOrEqualTo':
                    $entities = $entities->where($columnName, '>=', $value);
                    break;
                case 'IsGreaterThanOrNull':
                    $entities = $entities->filter(function ($entity) use ($columnName, $value) {
                        return ($entity->$columnName > $value || empty($entity->$columnName));
                    });
                    break;
                case 'IsGreaterThan':
                    $entities = $entities->where($columnName, '>', $value);
                    break;
                case 'IsLessThanOrEqualTo':
                    $entities = $entities->where($columnName, '<=', $value);
                    break;
                case 'IsLessThan':
                    $entities = $entities->where($columnName, '<', $value);
                    break;
                case 'IsAfterThanOrEqualTo':
                    $entities = $entities->where($columnName, '>=', $value);
                    break;
                case 'IsAfterThan':
                    $entities = $entities->where($columnName, '>', $value);
                    break;
                case 'IsBeforeThanOrEqualTo':
                    $entities = $entities->where($columnName, '<=', $value);
                    break;
                case 'IsBeforeThan':
                    $entities = $entities->where($columnName, '<', $value);
                    break;
            }
        }

        return $entities;
    }

    /**
     * @param Collection $entities
     * @param Order[]|Collection $orders
     * @return Collection
     */
    protected function processOrderWithCollection($entities, $orders)
    {
        $sortBy = [];
        foreach ($orders as $order) {
            $sortBy[$order->getColumnName()] = $order->getValue();
        }

        return $entities->sortBy($sortBy);
    }
}
