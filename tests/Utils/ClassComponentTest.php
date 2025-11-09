<?php
declare(strict_types=1);

namespace gijsbos\ClassParser\Classes;

use PHPUnit\Framework\TestCase;

final class ClassComponentTest extends TestCase
{
    public function testToStringNewConstant()
    {
        $classComponent = new ClassComponent(ClassComponent::NEW_CONSTANT, "IS_CONSTANT", ' "true"', "/**\n     * IS_CONSTANT\n     */");
        $result = $classComponent->toString();
        $expectedResult = <<< EOD
    /**
     * IS_CONSTANT
     */
    const IS_CONSTANT = "true";

EOD;
        $this->assertEquals($expectedResult, $result);
    }

    public function testToStringNewProperty()
    {
        $classComponent = new ClassComponent(ClassComponent::NEW_PROPERTY, "isProperty", ' "true"', "/**\n     * isProperty\n     */");
        $classComponent->isPublic = true;
        $result = $classComponent->toString();
        $expectedResult = <<< EOD
    /**
     * isProperty
     */
    public \$isProperty = "true";

EOD;
        $this->assertEquals($expectedResult, $result);
    }

    public function testToStringNewMethod()
    {
        $classComponent = new ClassComponent(ClassComponent::NEW_METHOD, "newMethod", "string \$input1, array \$input2", "/**\n     * newMethod\n     */");
        $classComponent->body =  <<< EOD
        return array(\$input1, \$input2);
EOD;
        $classComponent->returnType = "array";
        $classComponent->isPublic = true;
        $classComponent->curlyBracketOnNewline = true;
        $result = $classComponent->toString();
        $expectedResult = <<< EOD
    /**
     * newMethod
     */
    public function newMethod(string \$input1, array \$input2) : array
    {
        return array(\$input1, \$input2);
    }

EOD;
        $this->assertEquals($expectedResult, $result);
    }

    public function testToStringNewClass()
    {
        $classComponent = new ClassComponent(ClassComponent::NEW_CLASS, "NewClass", null, "/**\n * NewClass\n */");
        $classComponent->curlyBracketOnNewline = true;
        $classComponent->extends = "WDS\Extends";
        $classComponent->implements = "WDS\Implements";
        $classComponent->addComponent(new ClassComponent(ClassComponent::NEW_CONSTANT, "IS_CONSTANT", ' "true"', "/**\n     * IS_CONSTANT\n     */"));
        $classComponent->addComponent(new ClassComponent(ClassComponent::NEW_PROPERTY, "isProperty", ' "true"', "/**\n     * isProperty\n     */"));
        $classComponent->addComponent(new ClassComponent(ClassComponent::NEW_METHOD, "newMethod", "string \$input1, array \$input2", "/**\n     * newMethod\n     */"));
        $result = $classComponent->toString();
        $expectedResult = <<<EOD
/**
 * NewClass
 */
class NewClass extends WDS\Extends implements WDS\Implements
{
    /**
     * IS_CONSTANT
     */
    const IS_CONSTANT = "true";
    /**
     * isProperty
     */
    \$isProperty = "true";
    /**
     * newMethod
     */
    function newMethod(string \$input1, array \$input2) {
    
    }
}
EOD;
        $this->assertEquals($expectedResult, $result);
    }
}