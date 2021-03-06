<?php

namespace EcomDev\TranslateToolTest\Tokenizer\Pattern\Translate;

use EcomDev\TranslateTool\Expression;
use EcomDev\TranslateTool\Tokenizer\Pattern\Translate\ThisHelperExpression;
use Funivan\PhpTokenizer\Collection;
use Funivan\PhpTokenizer\Pattern\Pattern;

class ThisHelperExpressionTest
    extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ThisHelperExpression
     */
    private $pattern;

    protected function setUp()
    {
        $this->pattern = new ThisHelperExpression();
    }

    /**
     * @param string $code
     * @param string $defaultScope
     * @param Expression[] $translations
     * @dataProvider dataProviderTranslateExpressions
     */
    public function testItParsesThisHelperTranslateExpression($code, $defaultScope, array $translations)
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
                '$variable; $this->helper(false)->__(\'translation string\'); $this->helper(\'checkout\')->__(\'translation string2\\\'\');',
                'scope1',
                [
                    new Expression('translation string', 'scope1'),
                    new Expression('translation string2\'', 'checkout')
                ]
            ],
            [
                "\$variable;\n\$this->helper('test2')->__('translation string' \n . ' part 2' . \"\n\");\n\$this->helper(false)->__('translation string2');",
                'scope1',
                [
                    new Expression("translation string part 2\n", 'test2'),
                    new Expression('translation string2', 'scope1')
                ]
            ],
            [
                "\$this\n->helper('scope1')->\n__('translation %d string', 1);",
                'scope2',
                [
                    new Expression('translation %d string', 'scope1')
                ]
            ],
            [
                "\$this\n->helper(false)->\n__('translation %d string', 1);",
                null,
                [
                    new Expression('translation %d string')
                ]
            ],
            [
                "\$this\n->\nhelper('my')\n->\n__('Inception %s string', \$this->helper('test')->__('Inception string inside')));",
                null,
                [
                    new Expression('Inception %s string', 'my'),
                    new Expression('Inception string inside', 'test')
                ]
            ]
        ];
    }
}
