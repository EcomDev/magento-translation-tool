<?php

namespace EcomDev\TranslateTool\Tokenizer\Pattern\Translate;

use EcomDev\TranslateTool\Expression;
use EcomDev\TranslateTool\ExpressionInterface;
use Funivan\PhpTokenizer\Pattern\Patterns\PatternInterface;
use Funivan\PhpTokenizer\QuerySequence\QuerySequence;
use Funivan\PhpTokenizer\Strategy\Strict;
use Funivan\PhpTokenizer\Token;

abstract class AbstractExpression
    implements PatternInterface
{
    /**
     * Expression lists
     *
     * @var ExpressionInterface[]
     */
    private $expressions = [];

    /**
     * Default scope for expressions
     *
     * @var string
     */
    private $defaultScope;

    /**
     * Adds an expression to a list of extensions
     *
     * @param ExpressionInterface $expression
     * @return $this
     */
    public function addExpression(ExpressionInterface $expression)
    {
        $this->expressions[] = $expression;
        return $this;
    }

    /**
     * Returns list of expressions added before
     *
     * @return ExpressionInterface[]
     */
    public function getExpressions()
    {
        return $this->expressions;
    }

    /**
     * Clears collected expression list
     *
     * @return $this
     */
    public function clearExpressions()
    {
        $this->expressions = [];
        return $this;
    }

    /**
     * Creates expression from specified arguments
     *
     * @param string $message
     * @param string|null $scope
     * @return Expression
     */
    public function createExpression($message, $scope = null)
    {
        if ($scope === null) {
            $scope = $this->defaultScope;
        }

        return new Expression($message, $scope);
    }

    /**
     * Sets default scope for translation
     *
     * @param string $scope
     * @return $this
     */
    public function setDefaultScope($scope)
    {
        $this->defaultScope = $scope;
        return $this;
    }

    /**
     * Extracts argument tokens
     *
     * @param QuerySequence $querySequence
     * @return Token[]
     */
    protected function extractArgument(QuerySequence $querySequence)
    {
        $argumentTokens = [];
        while ($querySequence->isValid()) {
            $token = $querySequence->strict(Strict::create()->valueNot([',', ')']));
            if ($token->isValid()) {
                $argumentTokens[] = $token;
            }
        }

        return $argumentTokens;
    }

    /**
     * Transform string tokens
     *
     * @param Token[] $stringTokens
     * @return string
     */
    protected function transformString($stringTokens)
    {
        $value = '';
        foreach ($stringTokens as $token) {
            switch ($token->getType()) {
                case T_CONSTANT_ENCAPSED_STRING:
                    $value .= stripcslashes(substr($token->getValue(), 1, -1));
                    break;
                case -1:
                    if ($token->getValue() === '.') {
                        break;
                    }
                default:
                    $value = false;
                    break 2;
            }
        }

        return $value;
    }
}
