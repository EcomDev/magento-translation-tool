<?php

namespace EcomDev\TranslateTool\Command\MagentoOne;

use EcomDev\TranslateTool\FileParser\MagentoOne\PhpFile;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use League\Csv\Writer;

class PhpScan extends Command
{
    const DS = DIRECTORY_SEPARATOR;

    public function configure()
    {
        $this->setName('scan-files');
        $this->setDescription('Scans php files and saves results as CSV file');
        $this->addOption(
            'path', 'p',
            InputOption::VALUE_REQUIRED,
            'Root path of Magento install',
            getcwd()
        );

        $this->addOption(
            'output', 'o',
            InputOption::VALUE_REQUIRED,
            'Path where to save CSV file',
            getcwd()
        );

        $this->addOption(
            'output-filename', 'f',
            InputOption::VALUE_REQUIRED,
            'Filename for csv file',
            'dummy.csv'
        );

        $this->addOption(
            'default-scope', 'd',
            InputOption::VALUE_REQUIRED,
            'default scope for translation',
            'unknown'
        );


        $this->addOption(
            'ignore', 'i',
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Keywords that are going to be used for file names to ignore them'
        );

        $this->addOption(
            'search-directory', 's',
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Search directories for translation search'
        );
    }

    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @param InputInterface $input An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return null|int null or 0 if everything went fine, or an error code
     *
     * @throws \LogicException When this abstract method is not implemented
     *
     * @see setCode()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $directories = $input->getOption('search-directory');
        $basePath = $input->getOption('path');
        $defaultScope = $input->getOption('default-scope');
        $parser = new PhpFile();
        $outputFilePath = $input->getOption('output') . self::DS . $input->getOption('output-filename');
        $writer = Writer::createFromPath($outputFilePath, 'w');
        $writer->setDelimiter(',')
            ->setEnclosure('"')
            ->setEscape('\\');

        $writer->insertOne(['scope', 'phrase', 'translation', 'ignore(y/n)', 'file where found']);

        $ignore = array_map('preg_quote', $input->getOption('ignore'));
        if ($ignore) {
            $ignore = '/(' . implode('|', $ignore) . ')/i';
        }

        $scanUnique = [];
        foreach ($directories as $directory) {
            $iterator = $this->createFileSystemIterator($basePath, $directory);
            foreach ($iterator as $phpFile) {
                $filePath = substr($phpFile->getPathName(), strlen($basePath) + 1);
                $output->write('Scanning <info>' . $filePath . '</info> ... ');
                if ($ignore && preg_match($ignore, $filePath)) {
                    $output->writeln('<comment>Ignored</comment>');
                    continue;
                }
                foreach ($parser->parse($phpFile->getPathName(), $defaultScope) as $expression) {
                    if (isset($scanUnique[$expression->getScope()][$expression->getMessage()])) {
                        continue;
                    }

                    $writer->insertOne([
                        $expression->getScope(),
                        $expression->getMessage(),
                        '', '', $filePath
                    ]);

                    $scanUnique[$expression->getScope()][$expression->getMessage()] = true;
                }
                $output->writeln('Done');
            }
        }

        return 0;
    }

    private function createFileSystemIterator($path, $directory)
    {
        return new \RegexIterator(
                new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path . self::DS . trim($directory, self::DS))
                ),
                '/^.+\.(php|phtml)$/i',
                \RegexIterator::MATCH
            );
    }
}
