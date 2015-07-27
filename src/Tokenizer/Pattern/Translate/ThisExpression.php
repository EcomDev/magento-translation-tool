<?php

namespace EcomDev\TranslateTool\Tokenizer\Pattern\Translate;

use Funivan\PhpTokenizer\Collection;
use Funivan\PhpTokenizer\QuerySequence\QuerySequence;
use Funivan\PhpTokenizer\Strategy\Strict;

/**
 * Pattern of parsing tokens related to $this->__() calls
 *
 */
class ThisExpression extends AbstractExpression
{
    /**
     *
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

        $this->addExpression(
            $this->createExpression($stringMessage)
        );
    }
}
