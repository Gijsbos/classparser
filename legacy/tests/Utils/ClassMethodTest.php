<?php
declare(strict_types=1);

namespace gijsbos\ClassParser\Classes;

use PHPUnit\Framework\TestCase;

// Include TestClass
include_once("./tests/Files/TestClass.php");

final class ClassMethodTest extends TestCase
{
    public function testGetReturnStatement()
    {
        $this->assertTrue(true);
    }
}