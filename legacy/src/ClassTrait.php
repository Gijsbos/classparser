<?php
declare(strict_types=1);

namespace gijsbos\ClassParser\Classes;

/**
 * ClassTrait
 */
class ClassTrait
{
    private $line;
    public $text;
    public $component;
    
    /**
     * __construct
     */
    public function __construct(private $object, private string $name)
    {
        // Init props
        $this->line = 0;
        $this->text = "";
        $this->component = null;
    }

    /**
     * getClassName
     */
    public function getClassName()
    {
        return $this->object;
    }

    /**
     * getName
     */
    public function getName()
    {
        return $this->name;
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