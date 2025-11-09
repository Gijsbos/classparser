<?php
declare(strict_types=1);

namespace gijsbos\ClassParser\Classes;

/**
 * ClassMethod
 */
class ClassMethod extends \ReflectionMethod
{
    /**
     * @var string $text
     */
    public $text;

    /**
     * @var null|ClassComponent $component;
     */
    public $component;

    /**
     * __construct
     */
    public function __construct($object, string $method)
    {
        // Parent constructor
        parent::__construct($object, $method);

        // Init props
        $this->text = "";
        $this->component = null;
    }
    
    /**
     * equals
     */
    public function equals(ClassMethod $classMethod) : bool
    {
        return \hash_equals(hash("sha256", $this->text), hash("sha256", $classMethod->text));
    }

    /**
     * @return ClassComponent
     */
    public function getComponent()
    {
        return $this->component;
    }

    /**
     * toString
     */
    public function toString() : string
    {
        return $this->component ? $this->component->toString() : "";
    } 
}