<?php

namespace EcomDev\TranslateTool\FileParser\MagentoOne;

use EcomDev\TranslateTool\ExpressionInterface;
use EcomDev\TranslateTool\FileParserInterface;
use EcomDev\TranslateTool\Tokenizer\Pattern\FullClass;
use EcomDev\TranslateTool\Tokenizer\Pattern\PropertyValue;
use EcomDev\TranslateTool\Tokenizer\Pattern\Translate\HelperExpression;
use EcomDev\TranslateTool\Tokenizer\Pattern\Translate\ThisExpression;
use EcomDev\TranslateTool\Tokenizer\Pattern\Translate\ThisHelperExpression;

use Funivan\PhpTokenizer\Collection;
use Funivan\PhpTokenizer\File;
use Funivan\PhpTokenizer\Pattern\Pattern as PatternChecker;

class PhpFile implements FileParserInterface
{
    protected $patterns = [];

    public function __construct()
    {
        $this->patterns['class'] = new FullClass();
        $this->patterns['helper_expression'] = new HelperExpression();
        $this->patterns['this_expression'] = new ThisExpression();
        $this->patterns['this_helper_expression'] = new ThisHelperExpression();
    }

    /**
     * @param $filePath
     * @param $defaultScope
     * @return ExpressionInterface[]
     * @throws \InvalidArgumentException if file is not readable
     */
    public function parse($filePath, $defaultScope)
    {
        $file = File::open($filePath);
        $collection = $file->getCollection();
        $classesPattern = new PatternChecker($collection);
        $classesPattern->apply($this->patterns['class']);
        $collection->rewind();

        if ($classesPattern->getCollections()) {
            $result = [];
            foreach ($classesPattern->getCollections() as $classCollection) {
                foreach ($this->processPhpClass($classCollection, $defaultScope) as $item) {
                    $result[] = $item;
                }
            }

            return $result;
        }

        return $this->processPhpCode($collection, $defaultScope);
    }

    /**
     * @param Collection $collection
     * @param string $defaultScope
     * @return ExpressionInterface[]
     */
    private function processPhpClass(Collection $collection, $defaultScope)
    {
        $className = $collection->getFirst()->getValue();
        $collection->rewind();
        $scope = $defaultScope;
        $checkVariable = false;
        $allowedPatterns = ['helper_expression'];
        if (strpos($className, '_Block_') !== false) {
            $scope = substr($className, 0, strpos($className, '_Block_'));
            array_unshift($allowedPatterns, 'this_expression', 'this_helper_expression');
        } elseif (strpos($className, '_Helper_') !== false) {
            $checkVariable = '_moduleName';
            $scope = substr($className, 0, strpos($className, '_Helper_'));
            array_unshift($allowedPatterns, 'this_expression');
        } elseif (strlen($className) > 10 && substr($className, -10) === 'Controller') {
            $checkVariable = '_realModuleName';
            $scope = implode('_', array_slice(explode('_', $className, 3), 0, 2));
            array_unshift($allowedPatterns, 'this_expression');
        }

        if ($checkVariable) {
            $variablePatternChecker = new PatternChecker(clone $collection);
            $propertyValuePattern = new PropertyValue();
            $propertyValuePattern->filterPropertyName($checkVariable);
            $variablePatternChecker->apply($propertyValuePattern);
            if ($variablePatternChecker->getCollections()) {
                $token = current($variablePatternChecker->getCollections())->getFirst();
                $property = json_decode($token->getValue(), true);
                if (isset($property[$checkVariable])) {
                    $scope = $property[$checkVariable];
                }
            }
        }

        return $this->processPhpCode($collection, $scope, $allowedPatterns);
    }

    /**
     * @param Collection $collection
     * @param $defaultScope
     * @param array $allowedPatterns
     * @return ExpressionInterface[]
     */
    private function processPhpCode(
        Collection $collection,
        $defaultScope,
        $allowedPatterns = [
            'this_expression',
            'this_helper_expression',
            'helper_expression'
        ])
    {
        $expressions = [];
        foreach ($allowedPatterns as $patternCode) {
            /* @var $pattern \EcomDev\TranslateTool\Tokenizer\Pattern\Translate\AbstractExpression */
            $pattern = $this->patterns[$patternCode];
            $pattern->setDefaultScope($defaultScope);
            $pattern->clearExpressions();
            $patternChecker = new PatternChecker($collection);
            $patternChecker->apply($pattern);
            foreach ($pattern->getExpressions() as $expression) {
                $expressions[] = $expression;
            }
        }

        return $expressions;
    }
}
