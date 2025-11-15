<?php
declare(strict_types=1);

namespace gijsbos\ClassParser;

use PHPUnit\Framework\TestCase;

include_once "./tests/Files/TestClass.php";

final class ClassParserTest extends TestCase 
{
    public function testGetReflectionClass()
    {
        $reflectionClass = ClassParser::getReflectionClass("./tests/Files/TestClass.php");
        $result = $reflectionClass->getName();
        $expectedResult = "TestClass";
        $this->assertEquals($expectedResult, $result);
    }

    public function testParseFilePath()
    {
        $classObject = ClassParser::parse("./tests/Files/TestClass.php");
        $result = $classObject->toString();
        $expectedResult = $classObject->getText();
        $this->assertEquals($expectedResult, $result);
    }

    public function testParseFilePathLargeFile()
    {
        $classObject = ClassParser::parse("./src/ClassParser.php");
        $result = $classObject->toString();
        $expectedResult = preg_replace("/^[\s]+$/m", "", $classObject->getText()); // classObject prints the class cleaner than manual
        $this->assertEquals($expectedResult, $result);
    }

    public function testGetMethodAtLine()
    {
        $method = ClassParser::getMethodAtLine("./tests/Files/TestClass.php", 36);
        $result = $method->getName();
        $expectedResult = "__construct";
        $this->assertEquals($expectedResult, $result);
    }

    public function testGetMethodAtLineNotFound()
    {
        $this->expectExceptionMessage("Could not get method at line '41' in file './tests/Files/TestClass.php'");
        $method = ClassParser::getMethodAtLine("./tests/Files/TestClass.php", 41);
        $result = $method->getName();
        $expectedResult = "__construct";
    }

    public function testGetBody()
    {
        $classObject = ClassParser::parse("./tests/Files/TestClass.php");
        $result = ClassParser::getBody(reset($classObject->methods));
        $expectedResult = <<< EOD
            {
                \$this->variable1 = "init";
                \$this->variable2 = null;
            }
        EOD;
        $this->assertEquals($expectedResult, $result);
    }

    public function testGetBodyIncorrectInput()
    {
        $this->expectExceptionMessage("Invalid argument type for argument 'reflection' using type 'DateTime'");
        $result = ClassParser::getBody(new \DateTime());
    }
}