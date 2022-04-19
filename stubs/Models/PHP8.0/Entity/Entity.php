<?php

namespace Nanvaie\DatabaseRepository\Models\Entity;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

abstract class Entity implements JsonSerializable, Arrayable
{
    // contain originals value of attributes
    private $originals = [];

    /**
     * @return int
     */
    abstract public function getId();

    public function __construct()
    {

    }

    public function __set($name, $value)
    {
        if (property_exists($this, $name)) {
            $function = camel_case('set_' . snake_case($name));
            $this->$function($value);
        }
    }

    public function __get($name)
    {
        if (property_exists($this, $name)) {
            $function = camel_case('get_' . snake_case($name));
            return $this->$function();
        }
    }

    public function __isset($name)
    {
        return property_exists($this, $name);
    }

    /**
     * Make all variables of the object as null
     * @return $this
     */
    public function clearVariables()
    {
        $attributes = get_object_vars($this);
        foreach ($attributes as $attributeName => $attributeValue) {
            $this->$attributeName = null;
        }
        return $this;
    }

    /**
     * @return int
     */
    public function getPrimaryKey()
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
        return get_object_vars($this);
    }

    /**
     * store an array of attributes original value
     */
    public function storeOriginals()
    {
        $this->originals = $this->toArray();
    }

    /**
     * get an Array of Changed Attributes
     * @return array
     */
    public function changedAttributesName()
    {
        $changedAttributes = [];
        $attributes = $this->toArray();
        foreach ($attributes as $key => $value) {
            if (isset($this->originals[$key])) {
                if ($value != $this->originals[$key] && !((is_array($this->originals[$key]) || is_object($this->originals[$key])))) {
                    $changedAttributes[] = $key;
                }
            }
        }
        return $changedAttributes;
    }

    /**
     * get an Array of Changed Attributes with new values
     * @return array
     */
    public function getDirty()
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
     * @return array
     */
    public function getChanges()
    {
        $changes = [];

        foreach ($this->changedAttributesName() as $key) {
            $changes[$key] = $this->originals[$key];
        }

        return $changes;
    }

    /**
     * is any attribute changed?
     * @return bool
     */
    public function isDirty()
    {
        if (count($this->changedAttributesName()) > 0) return true;

        return false;
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }
}