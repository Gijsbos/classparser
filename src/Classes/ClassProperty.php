<?php
declare(strict_types=1);

namespace gijsbos\ClassParser\Classes;

use InvalidArgumentException;
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

    public static function create(array $params)
    {
        $indentation = @$params["indentation"] ?? "";
        $header = @$params["header"] ?? "";
        $visibility = @$params["visibility"] ?? "";
        $type = @$params["type"] ?? "";
        $static = @$params["static"] ?? false;
        $name = @$params["name"] ?? throw new InvalidArgumentException("Name is missing");
        $value = @$params["value"] ?? "";
        $footer = @$params["footer"] ?? "";

        return sprintf("$indentation%s$indentation%s%s%s%s%s%s", $header, strlen($visibility) ? "$visibility" : "", $static ? " static" : "", strlen($type) ? " $type" : "", " \$$name", strlen($value) ? " = $value;" : ";", $footer);
    }
}