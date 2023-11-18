<?php

namespace Eghamat24\DatabaseRepository\Models\Entity;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use JsonSerializable;

abstract class Entity implements JsonSerializable, Arrayable
{
    // contain originals value of attributes
    private $originals = [];

    abstract public function getId(): int;

    public function __construct()
    {

    }

    public function __set($name, $value)
    {
        if (property_exists($this, $name)) {
            $function = Str::camel('set_' . Str::snake($name));
            $this->$function($value);
        }
    }

    public function __get($name)
    {
        if (property_exists($this, $name)) {
            $function = Str::camel('get_' . Str::snake($name));
            return $this->$function();
        }
    }

    public function __isset($name)
    {
        return property_exists($this, $name);
    }

    /**
     * Make all variables of the object as null
     */
    public function clearVariables(): self
    {
        $attributes = get_object_vars($this);
        foreach ($attributes as $attributeName => $attributeValue) {
            $this->$attributeName = null;
        }
        return $this;
    }

    public function getPrimaryKey(): int
    {
        return $this->getId();
    }

    /**
     * Fill the model
     */
    public function fill()
    {

    }

    /**
     * get an Array of current Attributes value
     */
    public function toArray(): array
    {
        $attributes = get_object_vars($this);

        unset($attributes['originals']);

        return $attributes;
    }

    /**
     * store an array of attributes original value
     */
    public function storeOriginals()
    {
        $this->originals = $this->toArray();
    }

    /**
     * empty an array of attributes original value
     */
    public function emptyOriginals()
    {
        $this->originals = [];
    }

    /**
     * get an Array of Changed Attributes
     */
    public function changedAttributesName(): array
    {
        $changedAttributes = [];
        $attributes = $this->toArray();
        foreach ($attributes as $key => $value) {
            if (isset($this->originals[$key]) && $value !== $this->originals[$key] && ! ((is_array($this->originals[$key]) || is_object($this->originals[$key])))) {
                $changedAttributes[] = $key;
            }
        }
        return $changedAttributes;
    }

    /**
     * get an Array of Changed Attributes with new values
     */
    public function getDirty(): array
    {
        $dirty = [];
        $attributes = $this->toArray();

        foreach ($this->changedAttributesName() as $key) {
            $dirty[$key] = $attributes[$key];
        }

        return $dirty;
    }

    /**
     * get an Array of Changed Attributes with original values
     */
    public function getChanges(): array
    {
        $changes = [];

        foreach ($this->changedAttributesName() as $key) {
            $changes[$key] = $this->originals[$key];
        }

        return $changes;
    }

    /**
     * is any attribute changed?
     */
    public function isDirty(): bool
    {
        return count($this->changedAttributesName()) > 0;
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
