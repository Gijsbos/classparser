<?php
declare(strict_types=1);

namespace gijsbos\ClassParser\Classes;

use ReflectionMethod;

/**
 * ClassMethod
 */
class ClassMethod extends ReflectionMethod
{
    public string $header = "";
    public string $type = "";
    public string $static = "";
    public string $name = "";
    public string $args = "";
    public string $returnType = "";
    public string $definition = "";
    public null|int $definitionIndex = null;

    public function __construct($objectOrMethod, $method)
    {
        parent::__construct($objectOrMethod, $method);
    }

    public function toString()
    {
        return $this->definition;
    }
}