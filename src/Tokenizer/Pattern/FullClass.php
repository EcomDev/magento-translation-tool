<?php

namespace EcomDev\TranslateTool\Tokenizer\Pattern;

use Funivan\PhpTokenizer\Collection;
use Funivan\PhpTokenizer\Pattern\Patterns\PatternInterface;
use Funivan\PhpTokenizer\QuerySequence\QuerySequence;
use Funivan\PhpTokenizer\Strategy\Strict;
use Funivan\PhpTokenizer\Token;

class FullClass implements PatternInterface
{
    /**
     * Invoked on a query sequence
     *
     * @param QuerySequence $querySequence
     * @return Collection
     */
    public function __invoke(QuerySequence $querySequence)
    {
        $querySequence->setSkipWhitespaces(true);
        $className = $this->collectClassName($querySequence);

        if ($className === false) {
            return;
        }

        $body = $querySequence->section('{', '}');

        if ($querySequence->isValid()) {
            $collection = $body->extractItems(1, -1);
        }

        if (!$querySequence->isValid()) {
            return;
        }

        $token = new Token();
        $token->setType(T_STRING);
        $token->setValue($className);
        $token->setIndex(0);

        $collection->prepend($token);
        foreach ($collection as $index => $token) {
            $token->setIndex($index);
        }

        return $collection;
    }

    private function collectClassName(QuerySequence $sequence)
    {
        $sequence->strict(T_CLASS);

        if (!$sequence->isValid()) {
            return false;
        }

        $position = $sequence->getPosition();
        $sequence->setPosition(0)
            ->search(T_NAMESPACE);

        $tokens = new Collection();
        if ($sequence->isValid()) {
            do {
                $token = $sequence->process(Strict::create()->typeIs(array(T_STRING, T_NS_SEPARATOR)));
                if (!$sequence->isValid()) {
                    break;
                }

                $tokens[] = $token;
            } while ($sequence->isValid());

            $tokens[] = new Token([T_NS_SEPARATOR, '\\', 1]);
        }

        $sequence
            ->setPosition($position)
            ->setValid(true);

        $className = $sequence->strict(T_STRING);

        if (!$sequence->isValid()) {
            return false;
        }

        $tokens[] = $className;
        $sequence->setPosition($className->getIndex());

        return (string)$tokens;
    }

}
