<?php
declare(strict_types=1);

namespace gijsbos\ClassParser\Classes;

use ReflectionProperty;

/**
 * ClassProperty
 */
class ClassProperty extends ReflectionProperty
{
    public string $header = "";
    public string $visibility = "";
    public string $type = "";
    public string $static = "";
    public string $name = "";
    public string $value = "";
    public string $definition = "";
    public null|int $definitionIndex = null;

    public function __construct($class, $property)
    {
        parent::__construct($class, $property);   
    }

    public function toString()
    {
        return $this->definition;
    }
}