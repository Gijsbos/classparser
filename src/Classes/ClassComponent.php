<?php
declare(strict_types=1);

namespace gijsbos\ClassParser\Classes;

/**
 * ClassComponent
 */
class ClassComponent
{
    const NEW_CONSTANT = 1;
    const NEW_PROPERTY = 2;
    const NEW_METHOD = 3;
    const NEW_CLASS = 4;

    /**
     * @var int type
     */
    public $type;

    /**
     * @var string name
     */
    public $name;

    /**
     * @var string value
     */
    public $value;

    /**
     * @var string docComment
     */
    public $docComment;

    /**
     * @var string inlineComment
     */
    public $inlineComment; // constant/property definitions

    /**
     * @var null|string body
     */
    public $body;

    /**
     * @var string text
     */
    public $text;
    public $returnType;
    public $extends;
    public $implements;
    public $namespace;
    public $indentation;
    public $trailingLineBreaks;
    public $curlyBracketOnNewline;
    public $isPublic;
    public $isPrivate;
    public $isProtected;
    public $isStatic;
    public $isFinal;
    public $isAbstract;
    public $isInterface;
    public $constants;
    public $properties;
    public $methods;
    public $parent;

    /**
     * __construct
     * 
     * @param int $type component type: NEW_CONSTANT/NEW_PROPERTY/NEW_METHOD/NEW_CLASS
     * @param string $name constant/property/method/class name
     * @param string $value constant/property value, method/class constructor (e.g. "string $input1, int $input2")
     * @param string $body method body (class body is created using method 'addComponent')
     * @param string $indentation indentation (method/class/comment body will prepend indentation too)
     * @param int $trailingLineBreaks line breaks after component
     * @param bool $curlyBracketOnNewline method/class curly bracket on newline
     */
    public function __construct(int $type, string $name, null|string $value = null, null|string $docComment = null)
    {
        $this->type = $type;
        $this->name = $name;
        $this->value = $value;
        $this->docComment = $docComment !== null ? $docComment : "";
        $this->inlineComment = "";
        $this->body = "";
        $this->text = "";
        $this->returnType = "";
        $this->extends = null;
        $this->implements = null;
        $this->namespace = null;
        $this->indentation = $type === self::NEW_CLASS ? "" : "    ";
        $this->trailingLineBreaks = 1;
        $this->curlyBracketOnNewline = false;
        $this->isPublic = false;
        $this->isPrivate = false;
        $this->isProtected = false;
        $this->isStatic = false;
        $this->isFinal = false;
        $this->isAbstract = false;
        $this->isInterface = false;
        $this->constants = [];
        $this->properties = [];
        $this->methods = [];
        $this->parent = null;
    }

    /**
     * hasConstant
     */
    public function hasConstant(string $name) : bool
    {
        return array_key_exists($name, $this->constants);
    }

    /**
     * getConstant
     */
    public function getConstant(string $name)
    {
        return $this->constants[$name];
    }

    /**
     * removeConstant
     */
    public function removeConstant(string $name) : void
    {
        if($this->hasConstant($name))
            unset($this->constants[$name]);
    }

    /**
     * hasProperty
     */
    public function hasProperty(string $name) : bool
    {
        return array_key_exists($name, $this->properties);
    }

    /**
     * getProperty
     */
    public function getProperty(string $name)
    {
        return $this->properties[$name];
    }

    /**
     * removeProperty
     */
    public function removeProperty(string $name) : void
    {
        if($this->hasProperty($name))
            unset($this->properties[$name]);
    }

    /**
     * hasMethod
     */
    public function hasMethod(string $name) : bool
    {
        return array_key_exists($name, $this->methods);
    }

    /**
     * getMethod
     */
    public function getMethod(string $name)
    {
        return $this->methods[$name];
    }

    /**
     * removeMethod
     */
    public function removeMethod(string $name) : void
    {
        if($this->hasMethod($name))
            unset($this->methods[$name]);
    }

    /**
     * getReturnType
     */
    public function getReturnType() : string
    {
        return $this->returnType;
    }

    /**
     * getComposedReturnType
     *  Returns the returnType if defined, otherwise searches the doc comment for the @var property.
     */
    public function getComposedReturnType() : string
    {
        if($this->returnType)
            return $this->returnType;

        if(is_string($this->docComment))
        {
            if(preg_match("/@var\s+(\w+)\s+(\w*)/", $this->docComment, $matches))
            {
                $type = $matches[1];
                $propertyName = $matches[1];

                if($type !== $this->name)
                    return $type;
            }
        }

        return "";
    }

    /**
     * setAccessType
     */
    public function setAccessType(string $accessType) : void
    {
        switch($accessType)
        {
            case "public":
                $this->isPublic = true;
            break;
            case "private":
                $this->isPrivate = true;
            break;
            case "protected":
                $this->isProtected = true;
            break;
        }
    }

    /**
     * getAccessTypeString
     */
    public function getAccessTypeString() : string
    {
        switch(true)
        {
            case $this->isPublic:
                return "public";
            case $this->isPrivate:
                return "private";
            case $this->isProtected:
                return "protected";
            default:
                return "";
        }
    }

    /**
     * prependIndentation
     */
    private function prependIndentation(string $content) : string
    {
        foreach(($lines = explode("\n", $content)) as $i => $line)
            $lines[$i] = $this->indentation . $line;

        return implode("\n", $lines);
    }

    /**
     * printConstant
     */
    private function printConstant() : string
    {
        $docComment = $this->docComment !== "" ? $this->indentation . $this->docComment . "\n" : "";
        $visibility = $this->isPublic ? "public " : ($this->isPrivate ? "private " : ($this->isProtected ? "protected " : ""));
        $static = $this->isStatic ? "static " : "";
        $value = $this->value === null ? "" : " =" . $this->value;
        $trailingLineBreaks = \str_repeat("\n", $this->trailingLineBreaks);
        return sprintf("%s%s%s%sconst %s%s;%s%s", $docComment, $this->indentation, $visibility, $static, $this->name, $value, $this->inlineComment, $trailingLineBreaks);
    }

    /**
     * printProperty
     */
    private function printProperty() : string
    {
        $docComment = $this->docComment !== "" ? $this->indentation . $this->docComment . "\n" : "";
        $visibility = $this->isPublic ? "public " : ($this->isPrivate ? "private " : ($this->isProtected ? "protected " : ""));
        $static = $this->isStatic ? "static " : "";
        $value = $this->value === null ? "" : " =" . $this->value;
        $trailingLineBreaks = \str_repeat("\n", $this->trailingLineBreaks);
        return sprintf("%s%s%s%s\$%s%s;%s%s", $docComment, $this->indentation, $visibility, $static, $this->name, $value, $this->inlineComment, $trailingLineBreaks);
    }

    /**
     * printMethod
     */
    private function printMethod() : string
    {
        $docComment = $this->docComment !== "" ? $this->indentation . $this->docComment . "\n" : "";
        $visibility = $this->isPublic ? "public " : ($this->isPrivate ? "private " : ($this->isProtected ? "protected " : ""));
        $static = $this->isStatic ? "static " : "";
        $value = $this->value === null ? "()" : sprintf("(%s)", $this->value);
        $returnType = $this->returnType === "" ? "" : " : " . $this->returnType;

        // Create body
        $curlyBracketOnNewline = $this->curlyBracketOnNewline ? sprintf("\n%s{\n", $this->indentation) : sprintf(" {\n%s", $this->indentation);

        // Prepend lines in body with indentation
        $body = $this->body === null ? "" : $this->body;

        // Set body
        $body = $curlyBracketOnNewline . $body . sprintf("\n%s}%s", $this->indentation, \str_repeat("\n", $this->trailingLineBreaks));

        // Return result
        return sprintf("%s%s%s%sfunction %s%s%s%s", $docComment, $this->indentation, $visibility, $static, $this->name, $value, $returnType, $body);
    }

    /**
     * addComponent
     *  Allows adding class components when creating a component of type NEW_CLASS
     */
    public function addComponent(ClassComponent $classComponent) : void
    {
        // Make sure this is a ClassComponent of type NEW_CLASS
        if($this->type !== self::NEW_CLASS)
            throw new \Exception("Invalid operation trying to add a component to a ClassComponent of type " . $this->type);

        // Set parent
        $classComponent->parent = $this;

        // Switch
        switch($classComponent->type)
        {
            case self::NEW_CONSTANT:
                $this->constants[$classComponent->name] = $classComponent;
            break;
            case self::NEW_PROPERTY:
                $this->properties[$classComponent->name] = $classComponent;
            break;
            case self::NEW_METHOD:
                $this->methods[$classComponent->name] = $classComponent;
            break;
            default:
                throw new \Exception(sprintf("Could not add class component '%s', type unknown", $this->name));
        }
    }

    /**
     * printClass
     */
    private function printClass() : string
    {
        // Get doc comment
        $docComment = $this->docComment !== "" ? $this->indentation . $this->docComment . "\n" : "\n";

        // Get class/interface definition
        if($this->isInterface)
            $definition = "interface ";
        else
            $definition = ($this->isAbstract ? "abstract " : ($this->isFinal ? "final " : "")) . "class ";

        // Implements/extends
        $implementsExtends = $this->extends !== null ? " extends " . $this->extends : null;
        $implementsExtends = ($implementsExtends === null ? "" : $implementsExtends) . ($this->implements !== null ? " implements " . $this->implements : "");

        // Determine curly brackets on new line
        $curlyBracketOnNewline = $this->curlyBracketOnNewline ? "\n{\n" : " {\n";

        // Check if body is set manually
        if(strlen($this->body))
        {
            $body = $this->body;
        }
        else
        {
            // Get content
            $constants = array_map(function($contant){ return $contant->toString(); }, $this->constants);
            $properties = array_map(function($property){ return $property->toString(); }, $this->properties);
            $methods = array_map(function($method){ return $method->toString(); }, $this->methods);

            // Create body
            $body = implode("", $constants) . implode("", $properties) . implode("", $methods);
        }
        
        // Return result
        return sprintf("%s%s%s%s%s%s}", $docComment, $definition, $this->name, $implementsExtends, $curlyBracketOnNewline, $body);
    }

    /**
     * toString
     */
    public function toString() : string
    {
        switch($this->type)
        {
            case self::NEW_CONSTANT:
                return $this->printConstant();
            case self::NEW_PROPERTY:
                return $this->printProperty();
            case self::NEW_METHOD:
                return $this->printMethod();
            case self::NEW_CLASS:
                return $this->printClass();
            default:
                throw new \Exception("Could not print class component, unknown type '%d'", $this->type);
        }
    }

    /**
     * __debugInfo
     */
    public function __debugInfo()
    {
        $object = (array) $this;

        // Set entity
        if(array_key_exists("text", $object) && is_string($object["text"]))
            $object["text"] = sprintf("string(%d)", strlen($object["text"]));

        // Set entity
        if(array_key_exists("parent", $object) && !is_null($object["parent"]))
            $object["parent"] = sprintf("object(%s)#%d", get_class($object["parent"]), spl_object_id($object["parent"]));

        // Return data
        return $object;
    }
}