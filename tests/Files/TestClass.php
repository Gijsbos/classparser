<?php
declare(strict_types=1);

namespace Test;

use gijsbos\ClassParser\Classes\ClassObject;

include_once "./tests/Files/TestTrait.php";

/**
 * TestClass
 */
#[TestAttribute()]
final class TestClass extends ClassObject
{
    use TestTrait;

    const CONSTANT =    "value;name{ // }{ value }"; // Try to break the algorithm on " ';' and empty '{}' and non empty '{ value }'
    const CONSTANT_2 = "a-definition-to-test-for-trailing-newlines";

    public static 
        $value;

    /**
     * variable1
     */
    private $variable1;

    /**
     * variable2
     * @insert      <uuid4>
     * @regexp      "/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/"
     */
    public static $variable2 = "/^[\p{Sc}\p{L}0-9!@#%^*&()-=+;:'\",.?\n ]{1,256}$/u"; // Variable 2

    /**
     * protected
     */
    #[TestAttribute("/^[\w\.\:\-\, ]*$/")]
    protected $variable3 = array("input" => "value"); # Variable 3

    /**
     * __construct
     */
    public function __construct()
    {
        $this->variable1 = "init";
        $this->variable2 = null;
    }

    /**
     * testPrivateFunction
     */
    private function testPrivateFunction(string $input) : string
    {
        return $input;
    }

    /**
     * testPublicStaticFunction
     */
    public static function testPublicStaticFunction(string $input) : string
    {
        return $input;
    }

    /**
     * testProtectedFunction
     */
    protected function testProtectedFunction(array $args = array()) : array
    {
        return $args;
    }

    private static $variable4 = "tucked away in between code!";

    protected function testNoCommentFunction(array $args = array()) : array
    {
        return $args;
    }

    // protected function testForCommentSection() : array
    // {
    //     return true;
    // }

    protected function testForCommentSection() : array
    {
        return true;
    }

    #alt
    //comment
    #[TestAttribute([
        "multilineattribute"
    ])]
    protected function testAltComment()
    {
        // Function comment
        return true;
    }
    #[TestAttribute("/^[\p{Sc}\p{L}0-9!@#%^*&()-=+;:'\",.?\n ]{1,256}$/u")]
    protected function testAttribute()
    {
        // Function comment
        return true;
    }
}

// Extra

/**
 * TestClass
 */
final class TestClass2 extends ClassObject
{
}