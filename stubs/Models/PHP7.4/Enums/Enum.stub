<?php

namespace App\Models\Enums;

use ReflectionClass;

abstract class Enum
{
    public function getList(): array
    {
        return (new ReflectionClass($this))->getConstants();
    }

    /**
     * @param int|string $key
     * @return string|null
     */
    public function getValue($key): ?string
    {
        $list = $this->getList();
        $keys = array_keys($list);
        $key = is_numeric($key) ? (int)$key : $key;

        if (is_int($key) && $key < count($keys)) {
            $value = $list[$keys[$key]];
        } else {
            $value = $list[strtoupper($key)];
        }

        return $value;
    }

    /**
     * @param int|string $value
     * @return false|int|string
     */
    public function indexOf($value)
    {
        $list = $this->getList();
        $values = array_values($list);

        return array_search($value, $values, true);
    }
}