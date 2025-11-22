<?php
declare(strict_types=1);

namespace gijsbos\ClassParser\Classes;

/**
 * ClassConstant
 */
class ClassConstant extends \ReflectionClassConstant
{
    private $line;
    public $text;

    /**
     * @var null|ClassComponent $component;
     */
    public $component;
    
    /**
     * __construct
     */
    public function __construct($object, string $name)
    {
        // Parent constructor
        parent::__construct($object, $name);

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
        return $this->component ? $this->component->toString() : "";
    } 
}