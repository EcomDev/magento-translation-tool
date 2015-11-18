<?php

namespace EcomDev\TranslateTool\Command\MagentoOne;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use League\Csv\Writer;
use League\Csv\Reader;


class CsvExport extends Command
{
    public function configure()
    {
        $this->setName('csv-export');
        $this->setDescription('Export CSV files');
        $this->addOption(
            'merge-file', 'm',
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Merge existing files',
            []
        );

        $this->addOption(
            'source-file', 's',
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            []
        );

        $this->addArgument(
            'output',
            InputArgument::OPTIONAL,
            'Export file path',
            getcwd() . DIRECTORY_SEPARATOR . 'translate.csv'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mergedFiles = $input->getOption('merge-file');
        $sourceFiles = $input->getOption('source-file');
        $translates = [];

        $translateFilter = function ($row) {
            return count($row) >= 2 && !empty($row[0]) && !empty($row[1]);
        };

        foreach ($mergedFiles as $file) {
            $reader = Reader::createFromPath($file);
            $reader->addFilter($translateFilter);
            foreach ($reader->query() as $row) {
                $translates[$row[0]] = $row[1];
            }
        }

        $sourceFilter = function ($row, $rowIndex) {
            return count($row) >= 4 && $rowIndex !== 0;
        };

        foreach ($sourceFiles as $file) {
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
                if (!isset($translates[$row['phrase']]) && trim($row['translation']) !== '') {
                    $translates[$row['phrase']] = trim($row['translation']);
                }
            }
        }

        $writer = Writer::createFromPath($input->getArgument('output'), 'w');
        $writer->setEscape('\\')
            ->setEnclosure('"')
            ->setDelimiter(',');

        foreach ($translates as $source => $translate) {
            $writer->insertOne([$source, $translate]);
        }
    }
}
