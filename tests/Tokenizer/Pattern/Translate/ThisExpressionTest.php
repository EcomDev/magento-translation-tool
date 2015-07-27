<?php

namespace EcomDev\TranslateToolTest\Tokenizer\Pattern\Translate;

use EcomDev\TranslateTool\Expression;
use EcomDev\TranslateTool\Tokenizer\Pattern\Translate\ThisExpression;
use Funivan\PhpTokenizer\Collection;
use Funivan\PhpTokenizer\Pattern\Pattern;

class ThisExpressionTest
    extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ThisExpression
     */
    private $pattern;

    protected function setUp()
    {
        $this->pattern = new ThisExpression();
    }

    /**
     * @param string $code
     * @param string $defaultScope
     * @param Expression[] $translations
     * @dataProvider dataProviderTranslateExpressions
     */
    public function testItParsesThisTranslateExpression($code, $defaultScope, array $translations)
    {
        $patternChecker = new Pattern(Collection::initFromString('<?php ' . $code));
        $this->pattern->setDefaultScope($defaultScope);
        $patternChecker->apply($this->pattern);
        $this->assertEquals($translations, $this->pattern->getExpressions());
    }

    public function dataProviderTranslateExpressions()
    {
        return [
            [
                '$variable; $this->__(\'translation string\'); $this->__(\'translation string2\\\'\');',
                'scope1',
                [
                    new Expression('translation string', 'scope1'),
                    new Expression('translation string2\'', 'scope1')
                ]
            ],
            [
                "\$variable;\n\$this->__('translation string' \n . ' part 2' . \"\n\");\n\$this->__('translation string2');",
                'scope1',
                [
                    new Expression("translation string part 2\n", 'scope1'),
                    new Expression('translation string2', 'scope1')
                ]
            ],
            [
                "\$this\n->\n__('translation %d string', 1);",
                'scope1',
                [
                    new Expression('translation %d string', 'scope1')
                ]
            ],
            [
                "\$this\n->\n__('translation %d string', 1);",
                null,
                [
                    new Expression('translation %d string')
                ]
            ],
            [
                "\$this\n->\n__('Inception %s string', \$this->__('Inception string inside')));",
                null,
                [
                    new Expression('Inception %s string'),
                    new Expression('Inception string inside'),
                ]
            ]
        ];
    }
}
