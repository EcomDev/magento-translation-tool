<?php

namespace EcomDev\TranslateTool\Tokenizer\Pattern;

use Funivan\PhpTokenizer\Collection;
use Funivan\PhpTokenizer\Pattern\Patterns\PatternInterface;
use Funivan\PhpTokenizer\Query\Query;
use Funivan\PhpTokenizer\QuerySequence\QuerySequence;
use Funivan\PhpTokenizer\Strategy\Strict;
use Funivan\PhpTokenizer\Token;

class PropertyValue implements PatternInterface
{
    /**
     * @var Query
     */
    private $filterValue;

    /**
     * Initializes empty query
     *
     */
    public function __construct()
    {
        $this->filterPropertyName(null);
    }


    /**
     * Looks query property
     *
     * @param QuerySequence $querySequence
     * @return Collection|null
     */
    public function __invoke(QuerySequence $querySequence)
    {
        $querySequence->setSkipWhitespaces(true);

        $querySequence->strict(Strict::create()->typeIs([T_PRIVATE, T_PROTECTED, T_PUBLIC]));
        $variableToken = $querySequence->strict($this->filterValue);

        if (!$querySequence->isValid()) {
            return;
        }

        $name = substr($variableToken->getValue(), 1);
        $value = null;

        $querySequence->strict(
            Strict::create()
                ->typeIs(-1)
                ->valueIs('=')
        );

        /* @var Token[] $valueTokens */
        $valueTokens = [];
        while ($querySequence->isValid()) {
            $token = $querySequence->strict(
                Strict::create()
                    ->valueNot(';')
            );
            if ($token->isValid()) {
                $valueTokens[] = $token;
            }
        }

        if ($valueTokens) {
            $value = '';
            foreach ($valueTokens as $token) {
                if ($token->getType() === T_STRING) {
                    switch ($token->getValue()) {
                        case 'true':
                            $value = true;
                            break;
                        case 'false':
                            $value = false;
                            break;
                        case 'null';
                            $value = null;
                            break;
                    }
                    break;
                } elseif ($token->getType() === T_CONSTANT_ENCAPSED_STRING) {
                    $value .= stripslashes(trim($token->getValue(), '"\''));
                }
            }
        }

        $variable = [$name => $value];

        $token = new Token();
        $token
            ->setType(-1)
            ->setValue(json_encode($variable))
        ;

        $collection = new Collection();
        $collection->append($token);

        return $collection;
    }

    /**
     * @param null $filterValue
     * @return $this
     */
    public function filterPropertyName($filterValue)
    {
        if ($filterValue === null) {
            $this->filterValue = Strict::create()->valueLike('!\$.*!');
        } else {
            $this->filterValue = Strict::create()->valueIs('$' . $filterValue);
        }

        $this->filterValue->typeIs(T_VARIABLE);
        return $this;
    }
}
