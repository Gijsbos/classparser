<?php
declare(strict_types=1);

namespace gijsbos\ClassParser\Classes;

use gijsbos\ClassParser\ClassParser;
use PHPUnit\Framework\TestCase;

// Include TestClass
include_once("./tests/Files/TestTrait.php");
include_once("./tests/Files/TestAttribute.php");
include_once("./tests/Files/TestClass.php");
include_once("./tests/Files/TestClassEmpty.php");
include_once("./tests/Files/TestClassSingleFunction.php");

final class ClassObjectTest extends TestCase
{
    // public function testGetMethodAtLine1()
    // {
    //     $classObject = ClassParser::parse("./tests/Files/TestClass.php");
    //     $result = $classObject->getMethodAtLine(47)->name;
    //     $expectedResult = "testPrivateFunction";
    //     $this->assertEquals($expectedResult, $result);
    // }

    // public function testGetMethodAtLine2()
    // {
    //     $classObject = ClassParser::parse("./tests/Files/TestClass.php");
    //     $result = $classObject->getMethodAtLine(55)->name;
    //     $expectedResult = "testPublicStaticFunction";
    //     $this->assertEquals($expectedResult, $result);
    // }

    // public function testGetMethodAtLineNotFound()
    // {
    //     $this->expectExceptionMessage("Could not locate calling method name for class 'TestClass' at './tests/Files/TestClass.php:1'");
    //     $classObject = ClassParser::parse("./tests/Files/TestClass.php");
    //     $result = $classObject->getMethodAtLine(1);
    // }

    public function testToString()
    {
        // Load class object
        $classObject = ClassParser::parse("./tests/Files/AccessToken.php");

        // Add custom components to class object
        // $classObject->component->addComponent(new ClassComponent(ClassComponent::NEW_CONSTANT, "new_constant"));
        // $classObject->component->addComponent(new ClassComponent(ClassComponent::NEW_PROPERTY, "new_property"));
        // $classObject->component->addComponent(new ClassComponent(ClassComponent::NEW_METHOD, "new_method"));

        // Print object
        $result = $classObject->toString();
        var_dump($result);exit();
        $expectedResult = <<< EOD
<?php
declare(strict_types=1);

use gijsbos\ClassParser\Classes\ClassMethod;

/**
 * TestClass
 */
final class TestClass extends ClassMethod
{
    const CONSTANT =    "value;name{ // }{ value }"; // Try to break the algorithm on " ';' and empty '{}' and non empty '{ value }'
    const CONSTANT_2 = "a-definition-to-test-for-trailing-newlines";

    const new_constant;
    public static \$value;

    /**
     * variable1
     */
    private \$variable1;

    /**
     * variable2
     */
    public static \$variable2 = "/^[\p{Sc}\p{L}0-9!@#%^*&()-=+;:'\",.?\\n ]{1,256}\$/u"; // Variable 2

    /**
     * protected
     */
    #[TestAttribute("/^[\w\.\:\-\, ]*$/")]
    protected \$variable3 = array("input" => "value"); # Variable 3

    /**
     * __construct
     */
    public function __construct()
    {
        \$this->variable1 = "init";
        \$this->variable2 = null;
    }

    /**
     * testPrivateFunction
     */
    private function testPrivateFunction(string \$input) : string
    {
        return \$input;
    }

    /**
     * testPublicStaticFunction
     */
    public static function testPublicStaticFunction(string \$input) : string
    {
        return \$input;
    }

    /**
     * testProtectedFunction
     */
    protected function testProtectedFunction(array \$args = array()) : array
    {
        return \$args;
    }

    private static \$variable4 = "tucked away in between code!";

    \$new_property;
    protected function testNoCommentFunction(array \$args = array()) : array
    {
        return \$args;
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
    protected function testAltComment()
    {
        // Function comment
        return true;
    }
    #[TestAttribute("/^[\p{Sc}\p{L}0-9!@#%^*&()-=+;:'\",.?\\n ]{1,256}$/u")]
    protected function testAttribute()
    {
        // Function comment
        return true;
    }
    function new_method() {
    
    }
}

// Extra
EOD;
        $this->assertEquals($expectedResult, $result);
    }

    public function testToStringEmptyClass()
    {
        // Load class object
        $classObject = ClassParser::parse("./tests/Files/TestClassEmpty.php");

        // Add custom components to class object
        $classObject->component->addComponent(new ClassComponent(ClassComponent::NEW_CONSTANT, "new_constant"));
        $classObject->component->addComponent(new ClassComponent(ClassComponent::NEW_PROPERTY, "new_property"));
        $classObject->component->addComponent(new ClassComponent(ClassComponent::NEW_METHOD, "new_method"));

        // Print object
        $result = $classObject->toString();
        $expectedResult = <<< EOD
<?php
declare(strict_types=1);

/**
 * TestClassEmpty
 */
final class TestClassEmpty
{
    const new_constant;
    \$new_property;
    function new_method() {
    
    }
}
EOD;
        $this->assertEquals($expectedResult, $result);
    }

    public function testToStringSingleFunction()
    {
        // Load class object
        $classObject = ClassParser::parse("./tests/Files/TestClassSingleFunction.php");

        // Add custom components to class object
        $classObject->component->addComponent(new ClassComponent(ClassComponent::NEW_CONSTANT, "new_constant"));
        $classObject->component->addComponent(new ClassComponent(ClassComponent::NEW_PROPERTY, "new_property"));
        $classObject->component->addComponent(new ClassComponent(ClassComponent::NEW_METHOD, "new_method"));

        // Print object
        $result = $classObject->toString();
        $expectedResult = <<< EOD
<?php
declare(strict_types=1);

/**
 * TestClassSingleFunction
 */
final class TestClassSingleFunction
{
    const new_constant;
    \$new_property;
    public function single()
    {
        return "only one";
    }
    function new_method() {
    
    }
}
EOD;
        $this->assertEquals($expectedResult, $result);
    }
}