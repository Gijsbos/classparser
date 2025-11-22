<?php
declare(strict_types=1);

namespace gijsbos\ClassParser\Classes;

/**
 * ClassTrait
 */
class ClassTrait
{
    public string $header = "";
    public string $class = "";
    public string $name = "";
    public string $definition = "";
    public null|int $definitionIndex = null;

    public function __construct(string $class, string $name)
    {
        $this->class = $class;
        $this->name = $name;
    }

    public function toString()
    {
        return $this->definition;
    }
}