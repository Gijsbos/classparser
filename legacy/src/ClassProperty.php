<?php
declare(strict_types=1);

namespace gijsbos\ClassParser\Classes;

/**
 * ClassProperty
 */
class ClassProperty extends \ReflectionProperty
{
    private $line;
    public $text;
    public $component;
    
    /**
     * __construct
     */
    public function __construct($object, string $property)
    {
        // Parent constructor
        parent::__construct($object, $property);

        // Init props
        $this->line = 0;
        $this->text = "";
        $this->component = null;
    }

    /**
     * setLine
     */
    public function setLine(int $line) : void
    {
        $this->line = $line;
    }

    /**
     * getStartLine
     */
    public function getStartLine() : int
    {
        return $this->line;
    }

    /**
     * toString
     */
    public function toString() : string
    {
        if($this->component !== null)
            return $this->component->toString();

        return "";
    } 
}