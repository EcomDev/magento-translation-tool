<?php

namespace EcomDev\TranslateToolTest\Tokenizer\Pattern;

use EcomDev\TranslateTool\Tokenizer\Pattern\PropertyValue;
use Funivan\PhpTokenizer\Collection;
use Funivan\PhpTokenizer\Pattern\Pattern;

class PropertyValueTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PropertyValue
     */
    private $pattern;

    protected function setUp()
    {
        $this->pattern = new PropertyValue();
    }


    /**
     * @dataProvider dataProviderPropertyValue
     * @param string $code
     * @param string[] $parsedVariables
     * @param string|null $variableFilter
     */
    public function testItParsesVariablesFromTokens($code, $parsedVariables, $variableFilter = null)
    {
        $patternChecker = new Pattern(Collection::initFromString('<?php ' . $code));
        $this->pattern->filterPropertyName($variableFilter);
        $patternChecker->apply($this->pattern);
        $collections = $patternChecker->getCollections();
        $this->assertCount(count($parsedVariables), $collections);
        $index = 0;
        foreach ($parsedVariables as $name => $value) {
            $token = $collections[$index]->getFirst();
            $this->assertSame(
                json_encode([$name => $value]),
                $token->getValue()
            );
            $index ++;
        }
    }

    public function dataProviderPropertyValue()
    {
        return [
            [
                'class Test { protected $_variable = \'value\'; protected $_variable2; }',
                ['_variable' => 'value', '_variable2' => null]
            ],
            [
                'class Test { protected $_variable = \'value\'; protected $_variable2; }',
                ['_variable' => 'value'],
                '_variable'
            ],
            [
                'class Test { protected $_variable = \'value\\\'test\'; protected $_variable2; }',
                ['_variable' => 'value\'test'],
                '_variable'
            ],
            [
                'class Test { private $_variable2; }',
                ['_variable2' => null]
            ],
            [
                'class Test { private $_variable3 = false; }',
                ['_variable3' => false]
            ]
        ];
    }
}
