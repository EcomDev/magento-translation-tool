<?php

namespace EcomDev\TranslateToolTest\FileParser\MagentoOne;

use EcomDev\TranslateTool\Expression;
use EcomDev\TranslateTool\FileParser\MagentoOne\PhpFile;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class PhpFileTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Parser of php files
     *
     * @var PhpFile
     */
    private $parser;

    /**
     * File stream directory
     *
     * @var vfsStreamDirectory
     */
    private $fileSystem;

    protected function setUp()
    {
        $this->parser = new PhpFile();
        $this->fileSystem = vfsStream::setup('root', null, [
            'ControllerFileWithCustomScope.php' => "
            <?php
                class MyCustom_Module_Adminhtml_IndexController
                {
                    protected \$_realModuleName = 'Mage_Catalog';

                    public function testAction() {
                        \$this->__('Test translate with Mage_Catalog');
                        Mage::helper('customer')->__('Test translate with customer scope');
                    }
                }
            ",
            'ControllerFile.php' => "
            <?php
                class MyCustom_Module_Adminhtml_IndexController
                {
                    public function testAction() {
                        \$this->__('Test translate with MyCustom_Module');
                        Mage::helper('customer')->__('Test translate with customer scope');
                    }
                }
            ",
            'BlockFile.php' => "
            <?php
                class MyCustom_Module_Block_File
                {
                    public function someMethodName() {
                        \$this->__('Test translate with MyCustom_Module');
                        \$this->helper('customer')->__('Test translate with customer scope');
                        Mage::helper('customer2')->__('Test translate with customer2 scope');
                    }
                }
            ",
            'HelperFileWithScope.php' => "
            <?php
                class MyCustom_Module_Helper_File
                {
                    protected \$_moduleName = 'Mage_Checkout';

                    public function someMethodName() {
                        \$this->__('Test translate with Mage_Checkout');
                        \$this->helper('customer')->__('Test translate with customer scope');
                        Mage::helper('customer2')->__('Test translate with customer2 scope');
                    }
                }
            ",
            'HelperFileWithoutScope.php' => "
            <?php
                class MyCustom_Module_Helper_File
                {
                    public function someMethodName() {
                        \$this->__('Test translate with MyCustom_Module');
                        \$this->helper('customer')->__('Test translate with customer scope');
                        Mage::helper('customer2')->__('Test translate with customer2 scope');
                    }
                }
            ",
            'AntherPhpClassFile.php' => "
            <?php
                class JustAPHPClass
                {
                    public function someMethodName() {
                        \$this->__('Test translate with MyCustom_Module'); // Should be ignored
                        \$this->helper('customer')->__('Test translate with customer scope');
                        Mage::helper('customer2')->__('Test translate with customer2 scope');
                    }
                }
            ",
            'RegularPhpFile.php' => "
            <?php
                \$this->__('Test translate with default scope');
                \$this->helper('customer')->__('Test translate with customer scope');
                Mage::helper('customer2')->__('Test translate with customer2 scope');
            "
        ]);
    }

    /**
     * @param string $fileName
     * @param string $defaultScope
     * @param Expression[] $expectedExpressions
     * @dataProvider dataProviderPhpFiles
     */
    public function testItParsesPhpFileCorrectly($fileName, $defaultScope, $expectedExpressions)
    {
        $filePath = $this->fileSystem->url() . '/' . $fileName;
        $expressions = $this->parser->parse($filePath, $defaultScope);

        $this->assertEquals(
            $expectedExpressions,
            $expressions
        );
    }

    public function dataProviderPhpFiles()
    {
        return [
            'scoped_controller' => [
                'ControllerFileWithCustomScope.php',
                'default',
                [
                    new Expression('Test translate with Mage_Catalog', 'Mage_Catalog'),
                    new Expression('Test translate with customer scope', 'customer'),
                ]
            ],
            'controller' => [
                'ControllerFile.php',
                'default',
                [
                    new Expression('Test translate with MyCustom_Module', 'MyCustom_Module'),
                    new Expression('Test translate with customer scope', 'customer'),
                ]
            ],
            'block' => [
                'BlockFile.php',
                'default',
                [
                    new Expression('Test translate with MyCustom_Module', 'MyCustom_Module'),
                    new Expression('Test translate with customer scope', 'customer'),
                    new Expression('Test translate with customer2 scope', 'customer2'),
                ]
            ],
            'helper_scoped' => [
                'HelperFileWithScope.php',
                'default',
                [
                    new Expression('Test translate with Mage_Checkout', 'Mage_Checkout'),
                    new Expression('Test translate with customer2 scope', 'customer2'),
                ]
            ],
            'helper' => [
                'HelperFileWithoutScope.php',
                'default',
                [
                    new Expression('Test translate with MyCustom_Module', 'MyCustom_Module'),
                    new Expression('Test translate with customer2 scope', 'customer2'),
                ]
            ],
            'regular_clss' => [ // Other php classes should have only Mage::helper() being parsed
                'AntherPhpClassFile.php',
                'default',
                [
                    new Expression('Test translate with customer2 scope', 'customer2'),
                ]
            ],
            'php_code' => [// Regular php classes (e.g. templates) should allow $this->__() parsing
                'RegularPhpFile.php',
                'default',
                [
                    new Expression('Test translate with default scope', 'default'),
                    new Expression('Test translate with customer scope', 'customer'),
                    new Expression('Test translate with customer2 scope', 'customer2')
                ]
            ]
        ];
    }

}
