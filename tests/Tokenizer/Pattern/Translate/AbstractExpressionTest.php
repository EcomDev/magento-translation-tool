<?php

namespace EcomDev\TranslateToolTest\Tokenizer\Pattern\Translate;

use EcomDev\TranslateTool\ExpressionInterface;
use EcomDev\TranslateTool\Tokenizer\Pattern\Translate\AbstractExpression;

class AbstractExpressionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AbstractExpression
     */
    private $pattern;

    protected function setUp()
    {
        $this->pattern = $this
            ->getMockForAbstractClass('EcomDev\TranslateTool\Tokenizer\Pattern\Translate\AbstractExpression');
    }

    /**
     * @return ExpressionInterface
     */
    private function newExpression()
    {
        return $this->prophesize('EcomDev\TranslateTool\ExpressionInterface')->reveal();
    }

    public function testItAddsAnExpressionInstance()
    {
        $expression = $this->newExpression();
        $this->assertSame($this->pattern, $this->pattern->addExpression($expression));
        $this->assertSame([$expression], $this->pattern->getExpressions());
    }

    public function testItReturnsEmptyExpressionListIfNoneAdded()
    {
        $this->assertSame([], $this->pattern->getExpressions());
    }

    public function testItAddsMultipleExpressionInstances()
    {
        $expressionOne = $this->newExpression();
        $expressionTwo = $this->newExpression();
        $this->pattern->addExpression($expressionOne);
        $this->pattern->addExpression($expressionTwo);
        $this->assertSame([$expressionOne, $expressionTwo], $this->pattern->getExpressions());
    }

    public function testItClearsAddedExpressions()
    {
        $expressionOne = $this->newExpression();
        $this->pattern->addExpression($expressionOne);
        $this->assertSame($this->pattern, $this->pattern->clearExpressions());
        $this->assertSame([], $this->pattern->getExpressions());
    }

    public function testItCreatesANewInstanceOfExpression()
    {
        $expression = $this->pattern->createExpression('My message', 'my_scope');
        $this->assertInstanceOf('EcomDev\TranslateTool\ExpressionInterface', $expression);
        $this->assertSame('My message', $expression->getMessage());
        $this->assertSame('my_scope', $expression->getScope());
    }

    public function testItCreatesANewInstanceWithDefaultScopeIfNoneSpecified()
    {
        $this->assertSame($this->pattern, $this->pattern->setDefaultScope('scope_1'));
        $expression = $this->pattern->createExpression('My message');
        $this->assertInstanceOf('EcomDev\TranslateTool\ExpressionInterface', $expression);
        $this->assertSame('My message', $expression->getMessage());
        $this->assertSame('scope_1', $expression->getScope());
    }
}
