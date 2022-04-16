<?php

namespace Nanvaie\DatabaseRepository\Models\Enums;

use ReflectionClass;

abstract class Enum
{
    public function getList(): array
    {
        return (new ReflectionClass($this))->getConstants();
    }

    public function getValue(int|string $key): ?string
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

    public function indexOf(int|string $value): false|int|string
    {
        $list = $this->getList();
        $values = array_values($list);

        return array_search($value, $values, true);
    }
}