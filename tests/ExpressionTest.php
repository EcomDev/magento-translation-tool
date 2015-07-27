<?php

namespace EcomDev\TranslateToolTest;

use EcomDev\TranslateTool\Expression;

class ExpressionTest extends \PHPUnit_Framework_TestCase
{
    public function testItCreatesAnExpressionWithArgumentsSpecified()
    {
        $expression = new Expression('Some message', 'scope1');
        $this->assertSame('Some message', $expression->getMessage());
        $this->assertSame('scope1', $expression->getScope());
    }

    public function testItDoesNotSetScopeIfItIsNotSpecified()
    {
        $expression = new Expression('Another message');
        $this->assertSame('Another message', $expression->getMessage());
        $this->assertSame(null, $expression->getScope());
    }
}
