<?php

namespace EcomDev\TranslateToolTest\Tokenizer\Pattern;
use EcomDev\TranslateTool\Tokenizer\Pattern\FullClass;
use Funivan\PhpTokenizer\Collection;
use Funivan\PhpTokenizer\Pattern\Pattern;

class FullClassTest extends \PHPUnit_Framework_TestCase
{
    private $pattern;

    protected function setUp()
    {
        $this->pattern = new FullClass();
    }

    /**
     * @param $code
     * @param $expectedTokens
     * @dataProvider dataProviderPhpCode
     */
    public function testItFiltersOutClassesWithItsNameAndNameSpace($code, $expectedToken)
    {
        $patternChecker = new Pattern(Collection::initFromString('<?php ' . $code));
        $patternChecker = $patternChecker->apply($this->pattern);

        if ($expectedToken !== false) {
            $this->assertCount(1, $patternChecker->getCollections());
            /* @var $collection Collection */
            $collection = $patternChecker->getCollections()[0];
            $this->assertTrue(isset($collection[0]), 'Collection does not have a token at index 0');
            $token = $collection[0];
            $this->assertInstanceOf('Funivan\PhpTokenizer\Token', $token);
            $this->assertSame($expectedToken[0], $token->getType());
            $this->assertSame($expectedToken[1], $token->getValue());
        } else {
            $this->assertCount(0, $patternChecker->getCollections());
        }
    }

    /**
     * @return string[][]
     */
    public function dataProviderPhpCode()
    {
        return [
            ['$variable=1; namespace Test; class Test {}', [T_STRING,'Test\\Test']],
            ['/* Some comment */ namespace Test\\Test2; class Test {}', [T_STRING,'Test\\Test2\\Test']],
            ['function testName() {} class Test {}', [T_STRING,'Test']],
            ['function testName() {}', false],
        ];
    }
}
