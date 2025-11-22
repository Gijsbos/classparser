<?php
declare(strict_types=1);

namespace gijsbos\ClassParser\Classes;

include_once "./tests/Files/TestClass.php";

use gijsbos\ClassParser\ClassParser;
use PHPUnit\Framework\TestCase;

final class ClassParserTest extends TestCase
{
    public function testParse()
    {
        $classObjectList = ClassParser::parse("./tests/Files/TestClass.php", ["verbose" => false]);

        $this->assertEquals(2, count($classObjectList));

        $classObject = $classObjectList[0];

        $this->assertEquals($classObject->bodyHash, $classObject->calculateClassObjectsHash());
    }
}