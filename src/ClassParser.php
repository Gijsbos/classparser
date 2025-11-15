<?php
declare(strict_types=1);

namespace gijsbos\ClassParser;

use ReflectionClass;
use ReflectionMethod;
use ReflectionFunction;
use gijsbos\ClassParser\Classes\ClassComponent;
use gijsbos\ClassParser\Classes\ClassConstant;
use gijsbos\ClassParser\Classes\ClassMethod;
use gijsbos\ClassParser\Classes\ClassObject;
use gijsbos\ClassParser\Classes\ClassProperty;
use gijsbos\ExtFuncs\Utils\TextParser;

/**
 * ClassParser
 */
abstract class ClassParser
{
    /**
     * @var array $classMapCache
     *  Contains cache of key: filePath, value: className
     */
    public static $classMapCache = [];

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
     * getReflectionClass
     */
    public static function getReflectionClass(string $filePath, null|string &$fileContent = null, null|string &$fileContentNoComments = null) : ReflectionClass
    {
        return new ReflectionClass(self::getClassName($filePath, $fileContent, $fileContentNoComments));
    }

    /**
     * extractExtends
     */
    private static function extractExtends(string $input)
    {
        if(preg_match("/(?<!{)class.*extends\s+([a-zA-Z0-9_\\\]+)/si", $input, $matches))
            return $matches[1];
    
        return null;
    }

    /**
     * extractImplements
     */
    private static function extractImplements(string $input)
    {
        if(preg_match("/(?<!{)class.*implements\s+([a-zA-Z0-9_\\\]+)/si", $input, $matches))
            return $matches[1];
    
        return null;
    }

    /**
     * curlyBracketOnNewline
     */
    private static function curlyBracketOnNewline(int $startLine, string $fileContent) : bool
    {
        return !str_contains(trim(explode("\n", $fileContent)[$startLine - 1]), "{");
    }

    /**
     * createClassComponent
     */
    private static function createClassComponent(ReflectionClass $reflectionClass, string $fileContentsNoComments) : ClassComponent
    {
        $classComponent = new ClassComponent(ClassComponent::NEW_CLASS, $reflectionClass->getShortName(), null, ($docComment = $reflectionClass->getDocComment()) == false ? "" : $docComment);
        $classComponent->namespace = $reflectionClass->getNamespaceName();
        $classComponent->isFinal = $reflectionClass->isFinal();
        $classComponent->isAbstract = $reflectionClass->isAbstract();
        $classComponent->isInterface = $reflectionClass->isInterface();
        $classComponent->extends = self::extractExtends($fileContentsNoComments);
        $classComponent->implements = self::extractImplements($fileContentsNoComments);
        $classComponent->curlyBracketOnNewline = self::curlyBracketOnNewline($reflectionClass->getStartLine(), $fileContentsNoComments);
        return $classComponent;
    }

    /**
     * escape
     */
    private static function escape(string $fileContents) : string
    {
        $fileContents = TextParser::replaceCommentQuote($fileContents, function($match)
        {
            $match = str_replace("+", "U+002B", $match);
            $match = str_replace("{", "U+007B", $match);
            $match = str_replace("}", "U+007D", $match);
            $match = str_replace("(", "U+0028", $match);
            $match = str_replace(")", "U+0029", $match);
            $match = str_replace(";", "U+003B", $match);
            $match = str_replace("$", "U+0024", $match);
            return $match;
        });

        // Replace in parentheses
        $fileContents = replace_enclosed("(", ")", $fileContents, "$", "U+0024");

        // Return
        return $fileContents;
    }

    /**
     * unescape
     */
    private static function unescape(string $input) : string
    {
        $input = str_replace("U+007B", "{", $input);
        $input = str_replace("U+007D", "}", $input);
        $input = str_replace("U+0028", "(", $input);
        $input = str_replace("U+0029", ")", $input);
        $input = str_replace("U+003B", ";", $input);
        $input = str_replace("U+0024", "$", $input);
        $input = str_replace("U+002B", "+", $input);
        return $input;
    }

    /**
     * getExtra
     */
    private static function getExtra(string $fileContents) : string
    {
        if(preg_match("/(.*?{{[0]}})(.*)/s", $fileContents, $matches) == 1)
            return $matches[2];
        else
            return "";
    }

    /**
     * extractUses
     */
    private static function extractUses(string $fileContent) : array
    {
        // Match all
        preg_match_all("/[\t ]*use[\s]+([a-zA-Z0-9\_\\\]+)/", $fileContent, $matches);

        // Return
        return $matches[1];
    }

    /**
     * getUsesDefinitions
     * 
     * @param string|ReflectionClass className
     * @param array options - startsWith/endsWidth
     */
    public static function getUsesDefinitions($className, array $options = [])
    {
        if(is_string($className))
            $className = new ReflectionClass($className);

        // Extract
        $fileContents = file_get_contents($className->getFileName());

        // Return
        $usesDefinitions = self::extractUses($fileContents);

        // Filter starts with
        if($startsWith = array_option("startsWith", $options))
        {
            $usesDefinitions = array_filter($usesDefinitions, function($v) use ($startsWith)
            {
                return str_starts_with($v, $startsWith);
            });
        }

        // Filter ends with
        if($endsWith = array_option("endsWith", $options))
        {
            $usesDefinitions = array_filter($usesDefinitions, function($v) use ($endsWith)
            {
                return str_ends_with($v, $endsWith);
            });
        }

        //
        return $usesDefinitions;
    }

    /**
     * getClassHead
     */
    private static function getClassHead(ReflectionClass $reflectionClass, string $escapedFileContents) : string
    {
        // Get head of file
        $head = implode("\n", array_slice(explode("\n", $escapedFileContents), 0, $reflectionClass->getStartLine() - 1));

        // Unescape
        $head = self::unescape($head);
        
        // Get doc comment
        if(($docComment = $reflectionClass->getDocComment()) !== false)
        {
            $pos = strrpos($head, $docComment);

            // If false; then this is probably due to a mismatch between the file contents that have been read and the reflectionClass instance using a different file that does not contain the docComment
            if($pos === false)
                throw new \Exception("Could not locate docComment in class header");

            // Extract the head
            $head = substr($head, 0, $pos);
        }

        // Return head
        return $head;
    }

    /**
     * clearMethodBodies
     */
    private static function clearMethodBodies(string $classBody)
    {
        $placeholders = placeholder_replace("{", "}", $classBody);
        
        // Replace placeholders with newlines
        $placeholders = array_map(function($content){
            return str_repeat("\n", substr_count($content, "\n"));
        }, $placeholders);

        // Return restored
        return placeholder_restore($classBody, $placeholders);
    }

    /**
     * parseComments
     */
    private static function parseComments(ClassObject $classObject) : array
    {
        // Get class body
        $classBody = $classObject->body;

        // Comments
        $comments = [];

        // Clear function bodies
        $classBody = self::clearMethodBodies($classBody);

        // Match all comments
        preg_match_all("/^[\t ]+(?:\/\/.*|#.*|\/\*.*\*\/.*)$/m", $classBody, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        
        // Iterate over comments
        foreach($matches as $details)
        {
            $match = $details[0][0];
            $offset = intval($details[0][1]);
            $line = substr_count(substr($classBody, 0, $offset), "\n") + 1 + $classObject->getStartLine();
            $comments[$line] = self::unescape($match);
        }

        // Return result
        return $comments;
    }

    /**
     * createClassObject
     */
    private static function createClassObject(ReflectionClass $reflectionClass, string $filePath, string $escapedFileContents, string $fileContentsNoComments)
    {
        // Placeholder replace
        $placeholders = placeholder_replace("{", "}", $escapedFileContents);

        // Check if at minimum one placeholder is set containing class body
        if(count($placeholders) === 0)
            throw new \Exception(sprintf("Could not parse class body for class '%s' using file '%s'", $reflectionClass->getName(), $reflectionClass->getFileName()));

        // Create class object
        $classObject = new ClassObject($reflectionClass->getName(), $filePath);
        $classObject->body = $placeholders[0];
        $classObject->extra = self::unescape(placeholder_restore(self::getExtra($escapedFileContents), $placeholders));
        $classObject->uses = self::extractUses($fileContentsNoComments);
        $classObject->head = self::getClassHead($reflectionClass, $escapedFileContents);
        $classObject->comments = self::parseComments($classObject);

        // Return
        return $classObject;
    }

    /**
     * extractConstructorFromContent
     */
    private static function extractConstructorFromContent(string $content)
    {
        if(\str_contains($content, "(") && \str_contains($content, ")"))
            return self::unescape(placeholder_replace("(", ")", $content)[0]);

        return null;
    }

    /**
     * extractBody
     */
    private static function extractBody(string $body) : string
    {
        // Remove leading and trailing newlines
        $body = preg_replace("/^\n/", "", $body);
        $body = preg_replace("/[\s]*$/", "", $body);

        // Remove spacing in empty lines
        $body = preg_replace("/^[\s]+$/m", "", $body);

        // Return body
        return self::unescape($body);
    }

    /**
     * setComponentReturnType
     */
    private static function setComponentReturnType(ClassComponent $classComponent, string $content) : ClassComponent
    {
        if(preg_match("/\).+:\s*([\s\w\_\\\|]+)/s", $content, $matches) === 1)
        {
            $classComponent->returnType = trim($matches[1]);
        }
        
        return $classComponent;
    }

    /**
     * getTrailingNewlines
     */
    private static function getTrailingNewlines(string $classText, string $match) : string
    {
        // Get match position
        $pos = strpos($classText, $match);

        // Create sub string
        $sub = substr($classText, $pos + strlen($match));

        // Fetch space
        if(preg_match("/[\s]*/", $sub, $matches) === 1)
        {
            $spacing = $matches[0];
            $explode = explode("\n", $spacing);
            $newLines = implode("\n", array_slice($explode, 0, count($explode) - 1));
            
            return $newLines;
        }

        // Cannot be reached, regexp matches always
        return "";
    }

    /**
     * parseMethods
     */
    private static function parseMethods(ClassObject $classObject, string $classBody, array $placeholders, string $fileContents, string $escapedFileContents, string $fileContentsNoComments) : ClassObject
    {
        // Match all functions
        preg_match_all("/((?:[\t ]*#\[[\w+]+\][\t ]*)+)?([\t ]*)(public|private|protected)?[\s]*(static)?[\s]*function[\s]+(\w+)(\(.*?){{([0-9]+)}}/si", $classBody, $matches);

        // Iterate over functions
        foreach($matches[0] as $i => $match)
        {
            // Extract groups
            $match =            self::unescape(\placeholder_restore($match, $placeholders));
            $attributes =       $matches[1][$i];
            $indentation =      $matches[2][$i];
            $type =             $matches[3][$i];
            $static =           $matches[4][$i];
            $functionName =     $matches[5][$i];
            $content =          $matches[6][$i];
            $placeholderIndex = (int) $matches[7][$i];
            
            // Get value
            $value = self::extractConstructorFromContent($content);

            // Get function body
            $functionBody = self::extractBody($placeholders[$placeholderIndex]);

            // Create function
            $classMethod = new ClassMethod($classObject->getName(), $functionName);
            $classMethod->text = $match;

            // Create ClassComponent
            $classComponent = new ClassComponent(ClassComponent::NEW_METHOD, $functionName, $value);
            $classComponent = self::setComponentReturnType($classComponent, $content);
            $classComponent->docComment = ($docComment = $classMethod->getDocComment()) == false ? "" : $docComment;
            $classComponent->attributes = $attributes;
            $classComponent->body = $functionBody;
            $classComponent->text = $match;
            $classComponent->isPublic = $classMethod->isPublic();
            $classComponent->isPrivate = $classMethod->isPrivate();
            $classComponent->isProtected = $classMethod->isProtected();
            $classComponent->isStatic = $classMethod->isStatic();
            $classComponent->isFinal = $classMethod->isFinal();
            $classComponent->curlyBracketOnNewline = self::curlyBracketOnNewline($classMethod->getStartLine(), $escapedFileContents);
            $classComponent->indentation = $indentation;

            //Get trailing newlines
            $trailingNewLines = self::getTrailingNewlines($fileContents, $match);

            // Set trailing line breals
            $classComponent->trailingLineBreaks = substr_count($trailingNewLines, "\n") + 1;

            // Set text
            $classMethod->text .= $trailingNewLines;

            // Set classComponent
            $classMethod->component = $classComponent;
            
            // Add function
            $classObject->methods[$functionName] = $classMethod;
            $classObject->component->addComponent($classComponent);
         }
 
         // Return object
         return $classObject;
    }

    /**
     * getLineInText
     *  Get line information for properties/constants
     */
    private static function getLineInText(string $text, string $search) : int
    {
        // Remove spacing from search query
        $search = trim($search);

        // Search in text
        $searchPos = strpos($text, $search);

        // Check if search was found
        if($searchPos === false){
            throw new \Exception(sprintf("Could not find '%s' in class body text", $search));
        }

        // Set result text
        $result = substr($text, 0, $searchPos);

        // Create lines
        $lines = explode("\n", $result);

        // Return count
        return count($lines);
    }

    /**
     * parseConstants
     */
    private static function parseConstants(ClassObject $classObject, string $classBody, array $placeholders, string $fileContents, string $escapedFileContents, string $fileContentsNoComments) : ClassObject
    {
        // Match all functions
        preg_match_all("/\n?([\t ]*)const[\s]+([a-zA-Z0-9\_]+)(?:[\s]*=([\s]*(?:.*?)));(.*?)(?=\n)/si", $classBody, $matches);

        // Iterate over functions
        foreach($matches[0] as $i => $match)
        {
            // Extract groups
            $match =            self::unescape(placeholder_restore($match, $placeholders));
            $indentation =      $matches[1][$i];
            $variableName =     $matches[2][$i];
            $value =            strlen($value = self::unescape(placeholder_restore($matches[3][$i], $placeholders))) === 0 ? null : $value;
            $inlineComment =    self::unescape(placeholder_restore($matches[4][$i], $placeholders));

            // Create constant
            $classConstant = new ClassConstant($classObject->getName(), $variableName);
            $classConstant->setLine(self::getLineInText($fileContents, $match));
            $classConstant->text = $match;

            // Create class component constant
            $classComponent = new ClassComponent(ClassComponent::NEW_CONSTANT, $variableName, $value);
            $classComponent->indentation = $indentation;
            $classComponent->docComment = ($docComment = $classConstant->getDocComment()) == false ? "" : $docComment;
            $classComponent->inlineComment = $inlineComment;

            // Get trailing newlines
            $trailingNewLines = self::getTrailingNewlines($fileContents, $match);

            // Set trailing line breals
            $classComponent->trailingLineBreaks = substr_count($trailingNewLines, "\n") + 1;

            // Update text
            $classConstant->text .=  $trailingNewLines;

            // Set classComponent
            $classConstant->component = $classComponent;

            // Add constant
            $classObject->constants[$variableName] = $classConstant;
            $classObject->component->addComponent($classComponent);
        }

        // Return
        return $classObject;
    }

    /**
     * parseProperties
     */
    private static function parseProperties(ClassObject $classObject, string $classBody, array $placeholders, string $fileContents, string $escapedFileContents, string $fileContentsNoComments) : ClassObject
    {
        // Match all functions
        preg_match_all("/((?:[\t ]*#\[[\w+]+\][\t ]*)+)?([\t ]*)(public|private|protected)?[\s]*(static)?[\s]*\\$([a-zA-Z0-9\_]+)(?:[\s]*=([\s]*(?:.*?)))?;(.*?)(?=\n)/si", $classBody, $matches);

        // Iterate over functions
        foreach($matches[0] as $i => $match)
        {
            // Extract groups
            $match =                self::unescape(placeholder_restore($match, $placeholders));
            $attributes =           $matches[1][$i];
            $indentation =          $matches[2][$i];
            $type =                 $matches[3][$i];
            $static =               $matches[4][$i];
            $variableName =         $matches[5][$i];
            $value =                strlen($value = self::unescape(placeholder_restore($matches[6][$i], $placeholders))) === 0 ? null : $value;
            $inlineComment =        self::unescape(placeholder_restore($matches[7][$i], $placeholders));

            // Create constant
            $classProperty = new ClassProperty($classObject->getName(), $variableName);
            $classProperty->setLine(self::getLineInText($fileContents, $match));
            $classProperty->text = $match;

            // Create classComponent
            $classComponent = new ClassComponent(ClassComponent::NEW_PROPERTY, $variableName, $value);
            $classComponent->isPublic = $classProperty->isPublic();
            $classComponent->isPrivate = $classProperty->isPrivate();
            $classComponent->isProtected = $classProperty->isProtected();
            $classComponent->isStatic = $classProperty->isStatic();
            $classComponent->indentation = $indentation;
            $classComponent->docComment = ($docComment = $classProperty->getDocComment()) == false ? "" : $docComment;
            $classComponent->attributes = $attributes;
            $classComponent->inlineComment = $inlineComment;

            // Get trailing newlines
            $trailingNewLines = self::getTrailingNewlines($fileContents, $match);

            // Set trailing line breaks
            $classComponent->trailingLineBreaks = substr_count($trailingNewLines, "\n") + 1;

            // Update text
            $classProperty->text .=  $trailingNewLines;

            // Set classComponent
            $classProperty->component = $classComponent;

            // Add constant
            $classObject->properties[$variableName] = $classProperty;
            $classObject->component->addComponent($classComponent);
        }

        // Return
        return $classObject;
    }

    /**
     * parseClassBody
     */
    private static function parseClassBody(ClassObject $classObject, string $fileContents, string $escapedFileContents, string $fileContentsNoComments) : ClassObject
    {
        // Set classBody
        $classBody = $classObject->body;

        // Get placeholders
        $placeholders = placeholder_replace("{", "}", $classBody);

        // Parse methods
        $classObject = self::parseMethods($classObject, $classBody, $placeholders, $fileContents, $escapedFileContents, $fileContentsNoComments);

        // Parse constants
        $classObject = self::parseConstants($classObject, $classBody, $placeholders, $fileContents, $escapedFileContents, $fileContentsNoComments);

        // Parse properties
        $classObject = self::parseProperties($classObject, $classBody, $placeholders, $fileContents, $escapedFileContents, $fileContentsNoComments);

        // Return classObject
        return $classObject;
    }

    /**
     * parse
     */
    public static function parse(string $filePath) : ClassObject
    {
        // Get className
        self::getClassName($filePath, $reflectionClass);

        // Get file contents
        $fileContent = file_get_contents($filePath);

        // Create file contents without comments
        $fileContentNoComments = TextParser::removeComments($fileContent);

        // Create classComponent
        $classComponent = self::createClassComponent($reflectionClass, $fileContentNoComments);

        // Create escaped fileContents
        $escapedFileContents = self::escape($fileContent);
        
        // Create classObject
        $classObject = self::createClassObject($reflectionClass, $filePath, $escapedFileContents, $fileContentNoComments);

        // Set component
        $classObject->component = $classComponent;

        // Parse class body
        $classObject = self::parseClassBody($classObject, $fileContent, $escapedFileContents, $fileContentNoComments);

        // Unescape classBody
        $classObject->body = self::unescape($classObject->body);

        // Return result
        return $classObject;
    }

    /**
     * getMethodAtLine
     */
    public static function getMethodAtLine(string $filePath, int $line) : ReflectionMethod
    {
        // Get reflection class
        $reflectionClass = self::getReflectionClass($filePath);

        // Get methods between line
        $methods = array_filter($reflectionClass->getMethods(), function($method) use ($line) {
            return $line >= $method->getStartLine() && $line <= $method->getEndLine();
        });

        // Check result
        if(count($methods) === 0)
            throw new \Exception(sprintf("Could not get method at line '%d' in file '%s'", $line, $filePath));

        // Return
        return reset($methods);
    }

    /**
     * getBody
     */
    public static function getBody($reflection) : string
    {
        // Check input
        if(!$reflection instanceof ReflectionClass && !$reflection instanceof ReflectionMethod && !$reflection instanceof ReflectionFunction)
            throw new \InvalidArgumentException(sprintf("Invalid argument type for argument 'reflection' using type '%s'", ($type = gettype($reflection)) === "object" ? get_class($reflection) : $type));;
        
        // Get body 
        $fileContents = file_get_contents($reflection->getFileName());
        
        // Get function body
        $body = implode("\n", array_slice(explode("\n", $fileContents), $reflection->getStartLine(), $reflection->getEndLine() - $reflection->getStartLine()));

        // Return body
        return $body;
    }
}