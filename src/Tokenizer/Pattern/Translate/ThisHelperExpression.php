<?php

namespace EcomDev\TranslateTool\Tokenizer\Pattern\Translate;

use Funivan\PhpTokenizer\Collection;
use Funivan\PhpTokenizer\QuerySequence\QuerySequence;
use Funivan\PhpTokenizer\Strategy\Strict;

/**
 * Parses expressions like $this->helper('name')->__('String');
 *
 * Quite accurate if string constants are used
 */
class ThisHelperExpression extends AbstractExpression
{
    /**
     * @param QuerySequence $querySequence
     * @return Collection
     */
    public function __invoke(QuerySequence $querySequence)
    {
        $querySequence->setSkipWhitespaces(true);

        $querySequence->strict(
            Strict::create()
                ->typeIs(T_VARIABLE)
                ->valueIs('$this')
        );
        $querySequence->strict(T_OBJECT_OPERATOR);
        $querySequence->strict('helper');
        $helperNameTokens = iterator_to_array($querySequence->section('(', ')'));

        if (!$querySequence->isValid()) {
            return;
        }

        if ($helperNameTokens) {
            array_shift($helperNameTokens);
            $lastToken = array_pop($helperNameTokens);
            $querySequence->moveToToken($lastToken);
            $querySequence->strict(')');
            $querySequence->strict(T_OBJECT_OPERATOR);
        } else {
            return;
        }

        $helperName = $this->transformString($helperNameTokens);
        $scope = null;
        if ($helperName) {
            $scope = $helperName;
        }

        $querySequence->strict('__');
        $querySequence->strict('(');

        if (!$querySequence->isValid()) {
            return;
        }

        $stringTokens = $this->extractArgument($querySequence);
        $stringMessage = $this->transformString($stringTokens);
        if ($stringMessage === false) {
            return;
        }

        $this->addExpression($this->createExpression($stringMessage, $scope));
    }

}
