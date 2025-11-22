<?php
declare(strict_types=1);

namespace gijsbos\ClassParser\Classes;

/**
 * ClassObject
 *  Extends ReflectionClass /w text parsed code of constants, properties and methods.
 */
class ClassObject extends \ReflectionClass
{
    private $filePath;
    public $head;
    public $uses;
    public $body;
    public $comments;
    public $extra;
    public $constants;
    public $properties;
    public $methods;
    public $component;
    public $data;

    /**
     * __construct
     */
    public function __construct(string $className, null|string $filePath = null)
    {
        // Parent constructor
        parent::__construct($className);

        // Init props
        $this->filePath = $filePath;
        $this->head = "";
        $this->uses = [];
        $this->body = "";
        $this->comments = [];
        $this->extra = "";
        $this->constants = [];
        $this->properties = [];
        $this->methods = [];
        $this->component = new ClassComponent(ClassComponent::NEW_CLASS, $this->getShortName());
        $this->data = [];
    }

    /**
     * addData
     */
    public function addData(string $key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * getData
     */
    public function getData(string $key)
    {
        return $this->data[$key];
    }

    /**
     * getClassComponent
     */
    public function getClassComponent() : ClassComponent
    {
        return $this->component;
    }

    /**
     * hasMethod
     */
    public function hasMethod(string $methodName) : bool
    {
        return array_key_exists($methodName, $this->methods);
    }

    /**
     * getFilePath
     */
    public function getFilePath() : string
    {
        return $this->filePath !== null ? $this->filePath : $this->getFileName();
    }

    /**
     * getMethodAtLine
     */
    public function getMethodAtLine(int $line) : ClassMethod
    {
        foreach($this->methods as $method)
            if($line >= $method->getStartLine() && $line <= $method->getEndLine())
                return $method;

        throw new \Exception(sprintf("Could not locate calling method name for class '%s' at '%s:%d'", $this->getName(), $this->filePath, $line));
    }

    /**
     * getText
     */
    public function getText() : string
    {
        return \file_get_contents($this->getFileName());
    }

    /**
     * getContentArray
     */
    private function getContentArray() : array
    {
        // Create index for use
        $uses = array_map_assoc(function($key, $use)
        {
            if($use && $this->component->hasUse($use->getName()))
                return array($use->getStartLine(), $this->component->uses[$use->getName()]);

            return null;
        }, $this->uses);

        // Create index for constants
        $constants = array_map_assoc(function($key, $constant)
        {
            if($this->component->hasConstant($constant->getName()))
                return array($constant->getStartLine(), $this->component->constants[$constant->getName()]);

            return null;
        }, $this->constants);
        
        // Create index for properties
        $properties = array_map_assoc(function($key, $property)
        {
            if($this->component->hasProperty($property->getName()))
                return array($property->getStartLine(), $this->component->properties[$property->getName()]);

            return null;
        }, $this->properties);

        // Create index for methods
        $methods = array_map_assoc(function($key, $method)
        {
            if($this->component->hasMethod($method->getName()))
                return array($method->getStartLine(), $this->component->methods[$method->getName()]);

            return null;
        }, $this->methods);

        // Create merged
        $merged = array_replace($this->comments, $uses, $constants, $properties, $methods);

        // Sort on line
        ksort($merged);

        // Add custom components
        $content = array();

        // Check if content is empty, we need at least one item in the foreach loop
        if(count($merged) === 0)
            return array_merge($this->component->uses, $this->component->constants, $this->component->properties, $this->component->methods);

        // Iterate over items
        foreach($merged as $line => $value)
        {
            // Add comment
            if(is_string($value))
                $content[$line] = $value;

            // Check value
            if($value instanceof ClassComponent && $value->type === ClassComponent::NEW_USE)
            {
                // Add value
                $content[$line] = $value;

                // Remove
                unset($uses[$line]);
            }
            
            // Add constants in component that have not been included in ClassObject->constants
            if(count($uses) === 0)
                foreach(\array_diff_key($this->component->uses, $this->uses) as $key => $use)
                    $content[$key] = $use;

            // Check value
            if($value instanceof ClassComponent && $value->type === ClassComponent::NEW_CONSTANT)
            {
                // Add value
                $content[$line] = $value;

                // Remove
                unset($constants[$line]);
            }

            // Add constants in component that have not been included in ClassObject->constants
            if(count($constants) === 0)
                foreach(\array_diff_key($this->component->constants, $this->constants) as $key => $con)
                    $content[$key] = $con;

            // Check value
            if($value instanceof ClassComponent && $value->type === ClassComponent::NEW_PROPERTY)
            {
                // Add value
                $content[$line] = $value;

                // Remove
                unset($properties[$line]);
            }

            // Add properties in component that have not been included in ClassObject->properties
            if(count($properties) === 0)
                foreach(\array_diff_key($this->component->properties, $this->properties) as $key => $prop)
                    $content[$key] = $prop;

            // Check value
            if($value instanceof ClassComponent && $value->type === ClassComponent::NEW_METHOD)
            {
                // Add value
                $content[$line] = $value;

                // Remove
                unset($methods[$line]);
            }

            // Add methods in component that have not been included in ClassObject->methods
            if(count($methods) === 0)
                foreach(\array_diff_key($this->component->methods, $this->methods) as $key => $meth)
                    $content[$key] = $meth;
        }

        // Return result
        return $content;
    }

    /**
     * createClassBody
     */
    private function printClassBody()
    {
        // Get content
        $content = $this->getContentArray();

        // Set body
        $body = "";

        // Print content array
        foreach($content as $i => $item)
        {
            // Check if item is instance of ClassComponent
            if($item instanceof ClassComponent)
            {
                $body .= $item->toString();
            }
            else
            {
                // Check if at end of comment section, if so, give double enter
                if(@$content[$i + 1] !== null)
                    $body .= $item . "\n";
                else
                    $body .= $item . "\n\n";
            }
        }
        
        // Return body
        return $body;
    }

    /**
     * toString
     */
    public function toString() : string
    {
        // Create content
        $content = $this->head;

        // Copy component and set body
        $component = $this->component;

        // Set body
        $component->body = $this->printClassBody();

        // toString
        $content .= $component->toString();

        // Add extra
        $content .= $this->extra;

        // Return result
        return $content;
    }
}