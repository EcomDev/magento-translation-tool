<?php

namespace EcomDev\TranslateTool\Command\MagentoOne;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use League\Csv\Writer;
use League\Csv\Reader;


class CsvMissing extends Command
{
    public function configure()
    {
        $this->setName('csv-missing');
        $this->setDescription('Compares CSV files');
        $this->addOption(
            'current-file', 'c',
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Existing translation file',
            []
        );

        $this->addOption(
            'search-file', 's',
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            []
        );

        $this->addOption(
            'ignore-path', 'i',
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            []
        );

        $this->addArgument(
            'output',
            InputArgument::OPTIONAL,
            'Export file path',
            getcwd() . DIRECTORY_SEPARATOR . 'missing-translates.csv'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $currentFiles = $input->getOption('current-file');
        $searchFiles = $input->getOption('search-file');
        $ignorePath = $input->getOption('ignore-path');
        $translates = [];

        $translateFilter = function ($row) {
            return count($row) >= 2 && !empty($row[0]) && !empty($row[1]);
        };

        foreach ($currentFiles as $file) {
            $reader = Reader::createFromPath($file);
            $reader->addFilter($translateFilter);
            foreach ($reader->query() as $row) {
                $translates[$row[0]] = $row[1];
            }
        }

        $sourceFilter = function ($row, $rowIndex) {
            return count($row) >= 4 && $rowIndex !== 0;
        };

        $missingTranslatesColumns = ['scope', 'phrase', 'translation', 'ignore(y/n)','file where found'];
        $writer = Writer::createFromPath($input->getArgument('output'), 'w');
        $writer->setEscape('\\')
            ->setEnclosure('"')
            ->setDelimiter(',');

        $writer->insertOne($missingTranslatesColumns);

        foreach ($searchFiles as $file) {
            $reader = Reader::createFromPath($file);
            $sourceColumns = $reader->fetchOne(0);
            $sourceColumnsCount = count($sourceColumns);
            $reader->addFilter($sourceFilter);

            $mapSource = function (array $row) use ($sourceColumns, $sourceColumnsCount) {
                if ($sourceColumnsCount != count($row)) {
                    $row = array_slice(array_pad($row, $sourceColumnsCount, null), 0, $sourceColumnsCount);
                }

                return array_combine($sourceColumns, $row);
            };

            foreach ($reader->query($mapSource) as $row) {
                if (!isset($translates[$row['phrase']])) {
                    $writerRow = [];

                    foreach ($missingTranslatesColumns as $column) {
                        $writerRow[] = isset($row[$column]) ? $row[$column] : '';
                    }

                    $filePath = end($writerRow);

                    foreach ($ignorePath as $path) {
                        if (strpos($filePath, $path) !== false) {
                            continue 2;
                        }
                    }

                    $writer->insertOne($writerRow);
                }
            }
        }
    }
}
