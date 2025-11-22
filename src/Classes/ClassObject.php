<?php
declare(strict_types=1);

namespace gijsbos\ClassParser\Classes;

use InvalidArgumentException;

/**
 * ClassObject
 *  The ClassObject's goal is to extract information that is not available by the ReflectionClass.
 *  Used for reading literal file content and file generation.
 */
class ClassObject extends \ReflectionClass
{
    public array $uses = [];
    public string $extends = "";
    public string $implements = "";
    public array $traits = [];
    public array $constants = [];
    public array $properties = [];
    public array $methods = [];
    public array $definitions = []; // Contains every syntax definition with leading comments, attributes et cetera
    public string $header = "";
    public string $signature = "";
    public string $openParentheses = "";
    public string $closeParentheses = "";
    public string $definition = "";
    public null|int $definitionIndex = null;
    public string $body = "";
    public string $bodyHash = "";

    /**
     * __construct
     */
    public function __construct(object|string $objectOrClass)
    {
        parent::__construct($objectOrClass);
    }

    /**
     * setBody
     */
    public function setBody(string $body)
    {
        $this->body = $body;
        $this->bodyHash = hash('sha256', $body);
    }

    /**
     * addDefinition
     */
    public function addDefinition(string $definition, null|int $definitionIndex = null)
    {
        if($definitionIndex !== null)
        {
            if($definitionIndex > (count($this->definitions) + 1))
                throw new InvalidArgumentException("Definition index too large, cannot exceed n + 1");

            $pre = array_slice($this->definitions, 0, $definitionIndex);
            $post = array_slice($this->definitions, $definitionIndex);
            
            $result = [];
            array_push($result, ...$pre);
            array_push($result, $definition);
            array_push($result, ...$post);

            $this->definitions = $result;
        }
        else
        {
            array_push($this->definitions, $definition);
        }
    }

    /**
     * calculateClassObjectsHash
     *  Calculates the hash using the parsed class properties.
     *  If any method is changed, the calculated hash will differ from the bodyHash property.
     */
    public function calculateClassObjectsHash()
    {
        $count = count($this->traits) + count($this->constants) + count($this->properties) + count($this->methods);
        $merged = [];

        for($i = 0; $i < $count; $i++)
        {
            if(array_key_exists($i, $this->traits))
                $merged[$i] = $this->traits[$i];
            else if(array_key_exists($i, $this->constants))
                $merged[$i] = $this->constants[$i];
            else if(array_key_exists($i, $this->properties))
                $merged[$i] = $this->properties[$i];
            else if(array_key_exists($i, $this->methods))
                $merged[$i] = $this->methods[$i];
        }
        
        $text = implode("", array_map(fn($n) => $n->toString(), $merged));
        return hash('sha256', $text);
    }

    /**
     * calculateDefinitionsHash
     *  Calculates the hash using the parsed class properties.
     *  If any method is changed, the calculated hash will differ from the bodyHash property.
     */
    public function calculateDefinitionsHash()
    {
        $text = implode("", $this->definitions);
        return hash('sha256', $text);
    }

    /**
     * toString
     */
    public function toString()
    {
        $body = hash_equals($this->bodyHash, $this->calculateDefinitionsHash()) ? $this->body : implode("", $this->definitions);
        
        return sprintf("%s%s%s%s%s", $this->header, $this->signature, $this->openParentheses, $body, $this->closeParentheses);
    }
}