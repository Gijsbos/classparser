<?php
declare(strict_types=1);

namespace gijsbos\ClassParser;

use LogicException;
use ReflectionClass;
use gijsbos\ClassParser\Classes\ClassConstant;
use gijsbos\ClassParser\Classes\ClassMethod;
use gijsbos\ClassParser\Classes\ClassObject;
use gijsbos\ClassParser\Classes\ClassProperty;
use gijsbos\ClassParser\Classes\ClassTrait;
use gijsbos\Logging\Classes\LogEnabledClass;

/**
 * ClassParser
 */
class ClassParser extends LogEnabledClass
{
    /**
     * @var array $classMapCache
     *  Contains cache of key: filePath, value: className
     */
    public static $classMapCache = [];

    /**
     * Parse vars
     */
    public array $methodBodies;
    public array $commentBlocks;
    public array $blockComments;
    public array $slashComments;
    public array $quotes;
    public array $attributes;
    public array $hashComments;
    public array $parentheses;

    /**
     * __construct
     */
    public function __construct(array $opts = [])
    {
        parent::__construct($opts);

        $this->initVars();
    }

    /**
     * initVars
     */
    private function initVars()
    {
        $this->methodBodies = [];
        $this->commentBlocks = [];
        $this->blockComments = [];
        $this->slashComments = [];
        $this->quotes = [];
        $this->attributes = [];
        $this->hashComments = [];
        $this->parentheses = [];
    }

    /**
     * getClassName
     */
    public static function getClassName(string $filePath, null|ReflectionClass &$reflectionClass = null) : string
    {
        if(!is_file($filePath))
            throw new \Exception("File $filePath not found");
            
        // Get absolute path
        $filePath = realpath($filePath);

        // Check if path exists in included_files
        if(!in_array($filePath, get_included_files()))
            include $filePath;

        // File not found
        if(!is_file($filePath))
            throw new \Exception("Could not get class, file $filePath not found");

        // Check cache
        if(array_key_exists($filePath, self::$classMapCache))
        {
            $reflectionClass = self::$classMapCache[$filePath];
            return $reflectionClass->name;
        }

        // Get className
        foreach(get_declared_classes() as $className)
        {
            $reflection = new ReflectionClass($className);

            // Get class filename
            $classFileName = $reflection->getFileName();

            // Store in cache
            self::$classMapCache[$classFileName] = $reflection;

            // Classname found
            if($classFileName == $filePath)
            {
                $reflectionClass = $reflection;

                // Return className
                return $className;
            }
        }

        // File not found: good news is that cache now contains every filePath:className record
        throw new \Exception("Could not get class using $filePath");
    }

    /**
     * getClassBody
     */
    private function getClassBody(string $content, array $classBodies)
    {
        if(!preg_match("/{{(\d+)}}/", $content, $matches))
            throw new LogicException("Could not extract class body");

        return $classBodies[$matches[1]];
    }

    /**
     * reverseContent
     */
    public function reverseContent(string $content)
    {
        $content = preg_replace_callback("/{{parentheses-(\d+)}}/", function($matches) {
            $index = intval($matches[1]);
            return $this->parentheses[$index];
        }, $content);

        $content = preg_replace_callback("/{{hash-comment-(\d+)}}/", function($matches) {
            $index = intval($matches[1]);
            return $this->hashComments[$index];
        }, $content);
        
        $content = preg_replace_callback("/{{attribute-(\d+)}}/", function($matches) {
            $index = intval($matches[1]);
            return $this->attributes[$index];
        }, $content);

        $content = preg_replace_callback("/{{quote-(\d+)}}/", function($matches) {
            $index = intval($matches[1]);
            return $this->quotes[$index];
        }, $content);

        $content = preg_replace_callback("/{{slash-comment-(\d+)}}/", function($matches) {
            $index = intval($matches[1]);
            return $this->slashComments[$index];
        }, $content);

        $content = preg_replace_callback("/{{block-comment-(\d+)}}/", function($matches) {
            $index = intval($matches[1]);
            return $this->blockComments[$index];
        }, $content);

        $content = placeholder_restore($content, $this->methodBodies);

        return $content;
    }

    /**
     * parseClassBody
     * 
     *  Turns the class body into definition fragments per php syntax.
     * 
     *  For example when two properties A and B are defined, and B has an attribute and block comment, these belong to property B because php parsing is top down.
     *  Everything that is defined before property B until you reach property A definition, belongs to B.
     * 
     *  We therefore capture every "definition" through a series of capturing steps, turning code into placeholders for easier parsing.
     *  
     *  When the definitions are known, it becomes easy to parse the definitions individually and turn them into the appropriate objects.
     */
    public function parseClassBody(string $classBody)
    {
        // Put methods in placeholders
        $this->methodBodies = placeholder_replace("{", "}", $classBody);

        // Capture Block Comments
        $this->blockComments = [];
        $classBody = preg_replace_callback("/\/\*.+?(?=\*\/\n)\*\/\n/s", function($matches)
        {
            $matchString = $matches[0][0];
            $this->blockComments[] = $matchString;
            return "{{block-comment-".(count($this->blockComments)-1)."}}";
        }, $classBody, -1, $count, PREG_OFFSET_CAPTURE);

        // Replace slash comments with placeholders for later recovery
        $this->slashComments = [];
        $classBody = preg_replace_callback("/\/\/(?!.+;\s*\/\/).+/", function($matches)
        {
            $matchString = $matches[0][0];
            $offset = $matches[0][1];
            $this->slashComments[] = $matchString;
            return "{{slash-comment-".(count($this->slashComments)-1)."}}";
        }, $classBody, -1, $count, PREG_OFFSET_CAPTURE);

        // Replace quoted strings for later recovery
        $this->quotes = [];
        $classBody = preg_replace_callback("/(['\"`])(.+?)(?<!\\\\)\\1/s", function($matches)
        {
            $matchString = $matches[0][0];
            $offset = $matches[0][1];
            $this->quotes[] = $matchString;
            return "{{quote-".(count($this->quotes)-1)."}}";
        }, $classBody, -1, $count, PREG_OFFSET_CAPTURE);

        // Replace quoted strings for later recovery
        $this->attributes = [];
        $classBody = preg_replace_callback("/#\[[\w]\w+\(.+?(?=\]\s)\]/s", function($matches)
        {
            $matchString = $matches[0][0];
            $offset = $matches[0][1];
            $this->attributes[] = $matchString;
            return "{{attribute-".(count($this->attributes)-1)."}}";
        }, $classBody, -1, $count, PREG_OFFSET_CAPTURE);

        // Replace hash comments strings for later recovery
        $this->hashComments = [];
        $classBody = preg_replace_callback("/#.+$/m", function($matches)
        {
            $matchString = $matches[0][0];
            $offset = $matches[0][1];
            $this->hashComments[] = $matchString;
            return "{{hash-comment-".(count($this->hashComments)-1)."}}";
        }, $classBody, -1, $count, PREG_OFFSET_CAPTURE);

        // Les do dis
        $this->parentheses = [];
        $classBody = replace_enclosed_function("(", ")", $classBody, function($value)
        {
            $this->parentheses[] = $value;
            return "{{parentheses-".(count($this->parentheses)-1)."}}";
        });

        return $classBody;
    }

    /**
     * parseTrait
     */
    private function parseTrait(ClassObject $classObject, array $matchResult, array $matchData, string $definition, int $definitionIndex) : ClassTrait
    {
        $trait = new ClassTrait($classObject->getName(), $matchData["name"]);
        $trait->definition = $definition;
        $trait->definitionIndex = $definitionIndex;

        if(preg_match("/use\s+(.+)/s", $definition, $matches, PREG_OFFSET_CAPTURE))
        {
            $offset = $matches[0][1];
            $trait->header = substr($definition, 0, $offset);
        }

        return $trait;
    }

    /**
     * parseConstant
     */
    private function parseConstant(ClassObject $classObject, array $matchResult, array $matchData, string $definition, int $definitionIndex) : ClassConstant
    {
        $constant = new ClassConstant($classObject->getName(), $matchData["name"]);
        $constant->definition = $definition;
        $constant->definitionIndex = $definitionIndex;
        
        if(preg_match("/const\s+(.+)/", $definition, $matches, PREG_OFFSET_CAPTURE))
        {
            $offset = $matches[0][1];
            $constant->header = substr($definition, 0, $offset);
        }

        return $constant;
    }

    /**
     * parseProperty
     */
    private function parseProperty(ClassObject $classObject, array $matchResult, array $matchData, string $definition, int $definitionIndex) : ClassProperty
    {
        $property = new ClassProperty($classObject->getName(), $matchData["name"]);
        $property->definition = $definition;
        $property->definitionIndex = $definitionIndex;
        $property->type = $matchData["type"];
        $property->static = $matchData["static"];
        $property->value = $matchData["value"];
        
        if(preg_match("/const\s+(.+)/", $definition, $matches, PREG_OFFSET_CAPTURE))
        {
            $offset = $matches[0][1];
            $property->header = substr($definition, 0, $offset);
        }

        return $property;
    }

    /**
     * parseMethod
     */
    private function parseMethod(ClassObject $classObject, array $matchResult, array $matchData, string $definition, int $definitionIndex) : ClassMethod
    {
        $method = new ClassMethod($classObject->getName(), $matchData["name"]);
        $method->definition = $definition;
        $method->definitionIndex = $definitionIndex;
        $method->type = $matchData["type"];
        $method->static = $matchData["static"];
        $method->args = $matchData["args"];
        $method->returnType = $matchData["returnType"];
        
        if(preg_match("/const\s+(.+)/", $definition, $matches, PREG_OFFSET_CAPTURE))
        {
            $offset = $matches[0][1];
            $method->header = substr($definition, 0, $offset);
        }

        return $method;
    }

    /**
     * parseClassDefinition
     */
    private function parseClassDefinition(ClassObject $classObject, string $matchType, array $matchResult, array $matchData, string $definition, int $definitionIndex)
    {
        switch($matchType)
        {
            case "trait":
                $classObject->traits[$definitionIndex] = $this->parseTrait($classObject, $matchResult, $matchData, $definition, $definitionIndex);
            break;
            case "constant":
                $classObject->constants[$definitionIndex] = $this->parseConstant($classObject, $matchResult, $matchData, $definition, $definitionIndex);
            break;
            case "property":
                $classObject->properties[$definitionIndex] = $this->parseProperty($classObject, $matchResult, $matchData, $definition, $definitionIndex);
            break;
            case "method":
                $classObject->methods[$definitionIndex] = $this->parseMethod($classObject, $matchResult, $matchData, $definition, $definitionIndex);
            break;
            default:
                throw new LogicException("Could not parse match type '$matchType', type unknown");
        }
    }

    /**
     * createDefinitions
     */
    private function createDefinitions(ClassObject $classObject)
    {
        $parsedClassBody = $this->parseClassBody($classObject->body);
        
        // Declaration
        $definitions = [];
        $maxIterations = 999;
        $iterations = 0;
        while(strlen($parsedClassBody) > 0 && $iterations < $maxIterations)
        {
            $offset = 0;
            $matchOffset = null;
            $matchString = null;
            $matchResult = null;
            $matchType = null;
            $matchName = null;
            $matchData = []; // Specific data from match 

            // Class Traits
            if(preg_match("/\s+use\s+(.+?(?=;));\n/si", $parsedClassBody, $matches, PREG_OFFSET_CAPTURE))
            {
                $offset = $matches[0][1];

                if($matchOffset === null || $offset < $matchOffset)
                {
                    $matchString = $matches[0][0];
                    $matchOffset = $offset;
                    $matchResult = $matches;
                    $matchType = "trait";
                    $matchName = $matches[1][0];
                    $matchData["name"] = $matchName;
                }
            }

            // Class Constants
            if(preg_match("/\s+const\s+([\w]+)\s*=\s*(.+?(?=;));(.+?(?=\n))\n/si", $parsedClassBody, $matches, PREG_OFFSET_CAPTURE))
            {
                $offset = $matches[0][1];

                if($matchOffset === null || $offset < $matchOffset)
                {
                    $matchString = $matches[0][0];
                    $matchOffset = $offset;
                    $matchResult = $matches;
                    $matchType = "constant";
                    $matchName = $matches[1][0];
                    $matchData["name"] = $matchName;
                }
            }

            // Class Properties
            if(preg_match("/\s+(protected|public|private)?(?:\s+(static))?\s+\\$(\w+)(?:\s*;|\s*=(?:\s*(.+?(?=;));))?(.+?(?=\n))\n/si", $parsedClassBody, $matches, PREG_OFFSET_CAPTURE))
            {
                $offset = $matches[0][1];

                if($matchOffset === null || $offset < $matchOffset)
                {
                    $matchString = $matches[0][0];
                    $matchOffset = $offset;
                    $matchResult = $matches;
                    $matchType = "property";
                    $matchName = $matches[3][0];
                    $matchData["type"] = $matches[1][0];
                    $matchData["static"] = $matches[2][0];
                    $matchData["name"] = $matches[3][0];
                    $matchData["value"] = $matches[4][0];
                }
            }

            // Class Methods
            if(preg_match("/\s+(protected|public|private)(?:\s+(static))?\s+function\s+(\w+)\((.+?)\)(?:\s*:\s*(\w+))?.+?{{\d+}}\n/si", $parsedClassBody, $matches, PREG_OFFSET_CAPTURE))
            {
                $offset = $matches[0][1];

                if($matchOffset === null || $offset < $matchOffset)
                {
                    $matchString = $matches[0][0];
                    $matchOffset = $offset;
                    $matchResult = $matches;
                    $matchType = "method";
                    $matchName = $matches[3][0];
                    $matchData["type"] = $matches[1][0];
                    $matchData["static"] = $matches[2][0];
                    $matchData["name"] = $matches[3][0];
                    $matchData["args"] = $this->reverseContent($matches[4][0]);
                    $matchData["returnType"] = @$matches[5][0] ?? "";
                }
            }

            if($matchResult == null)
            {
                break;
            }
            else
            {
                log_info("Found $matchType: $matchName");
            }

            // Remove it from the parsedClassBody
            $fullPropertyDefinition = substr($parsedClassBody, 0, strlen($matchString) + $matchOffset);
            $parsedClassBody = substr($parsedClassBody, strlen($fullPropertyDefinition));

            // Get full declaration
            $definitionIndex = count($definitions);
            $definitions[] = $definition = $this->reverseContent($fullPropertyDefinition);

            // Parse definition
            $this->parseClassDefinition($classObject, $matchType, $matchResult, $matchData, $definition, $definitionIndex);

            // Safety check
            $iterations++;
        }
        
        return $definitions;
    }

    /**
     * readFile
     */
    private function readFile(string $filePath)
    {
        // Get file contents
        $fileContent = file_get_contents($filePath);

        // Create placeholders for classess
        $classBodies = placeholder_replace("{", "}", $fileContent);

        // Find class definition lines
        preg_match_all("/(\s*(final|abstract)?\s*class\s+(\w+)(?:\s+extends\s+([\w\\\\]+))?(?:\s+implements\s+(\w+))?)(.+?(?=\{)\{)\{\d+\}(\})/s", $fileContent, $matches, PREG_OFFSET_CAPTURE);

        // Parsing vars
        $fileHeader = null; // The header is everything that leads up to the class definition or signature
        $startOffset = 0;

        // Result stored in classObjects
        $classObjects = [];

        if(count($matches))
        {
            foreach($matches[0] as $i => $classMatch)
            {
                // Extract params
                $matchString = $classMatch[0];
                $signature = $matches[1][$i][0];
                $final = $matches[2][$i][0];
                $className = $matches[3][$i][0];
                $extends = $matches[4][$i][0];
                $implements = $matches[5][$i][0];
                $openParentheses = $matches[6][$i][0];
                $closeParentheses = $matches[7][$i][0];

                // Get offset
                $offset = intval($classMatch[1]);
                $header = substr($fileContent, $startOffset, $offset);
                $startOffset = strlen($header) + strlen($matchString);

                // Set header
                if($fileHeader == null)
                    $fileHeader = $header;

                // Extract namespac
                if(preg_match("/namespace\s+(\w+);/", $fileHeader, $namespaceMatches)) // Use fileHeader so if multiple classes are in one file, both will receive the same props
                {
                    $namespace = $namespaceMatches[1];
                }
                else
                {
                    $namespace = "";
                }

                // Extract uses
                preg_match_all("/use\s+(.+?);/m", $fileHeader, $usesMatches); // Use fileHeader so if multiple classes are in one file, both will receive the same props
                $uses = [];
                if(is_array($usesMatches[1]))
                {
                    foreach($usesMatches[1] as $usesMatch)
                    {
                        $uses[] = $usesMatch;
                    }
                }

                // Create classObject
                $className = strlen($namespace) > 0 ? "$namespace\\" . $className : $className;

                log_info("Parsed Class: $className");

                // Create object
                $classObject = new ClassObject($className);
                $classObject->uses = $uses;
                $classObject->extends = $extends; // As literal text from file (not resolved class)
                $classObject->implements = $implements; // As literal text from file (not resolved class)
                $classObject->header = $header;
                $classObject->signature = $signature;
                $classObject->openParentheses = $openParentheses;
                $classObject->closeParentheses = $closeParentheses;
                $classObject->definitionIndex = count($classObjects);

                // Use setBody to obtain hash
                $classObject->setBody(self::getClassBody($matchString, $classBodies));

                // Add object
                $classObjects[] = $classObject;
            }
        }

        return $classObjects;
    }

    /**
     * parseFile
     * 
     * @return ClassObject[]
     */
    public function parseFile(string $filePath) : array
    {
        log_info("Reading: $filePath");

        // Read file contents
        $classObjects = $this->readFile($filePath);
        
        // Parse the bodies
        foreach($classObjects as $classObject)
        {
            log_info("Reading: $filePath");

            // Create syntax definitions
            $classObject->definitions = $this->createDefinitions($classObject);

            // Reset parser vars
            $this->initVars();
        }

        // Return object
        return $classObjects;
    }

    /**
     * parse
     */
    public static function parse(string $filePath, array $opts = []) : array
    {
        return (new self($opts))->parseFile($filePath);
    }
}