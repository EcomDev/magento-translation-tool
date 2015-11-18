<?php

namespace EcomDev\TranslateTool\Command\MagentoOne;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use League\Csv\Writer;
use League\Csv\Reader;


class CsvCompareExported extends Command
{
    public function configure()
    {
        $this->setName('csv-compare-exported');
        $this->setDescription('Compares Exported CSV files');
        $this->addOption(
            'output', 'o',
            InputOption::VALUE_REQUIRED,
            'Path where to save CSV file',
            getcwd()
        );

        $this->addOption(
            'file-name', 'f',
            InputOption::VALUE_REQUIRED,
            'File name for an option',
            'diff.csv'
        );

        $this->addArgument('left-file', InputArgument::REQUIRED, 'Left csv file');
        $this->addArgument('right-file', InputArgument::REQUIRED, 'Right csv file');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sourceReader = Reader::createFromPath($input->getArgument('left-file'));
        $targetReader = Reader::createFromPath($input->getArgument('right-file'));


        $sourceArray = [];

        foreach ($sourceReader->query() as $row) {
            if (!empty($row[0])) {
                $sourceArray[$row[0]] = [$row[0], $row[1], '', ''];
            }
        }

        foreach ($targetReader->query() as $row) {

            if (!empty($row[0])) {
                $row[1] = trim($row[1]);
            }

            if (!empty($row[0]) && isset($sourceArray[$row[0]])) {
                if ($row[1] !== $sourceArray[$row[0]][1]) {
                    $sourceArray[$row[0]][2] = $sourceArray[$row[0]][1];
                    $sourceArray[$row[0]][1] = $row[1];
                }
            } elseif (!empty($row[0])) {
                $sourceArray[$row[0]] = ['', $row[1], '', $row[0]];
            }
        }

        $writer = Writer::createFromPath(
            $input->getOption('output') . DIRECTORY_SEPARATOR . $input->getOption('file-name'),
            'w'
        );

        $writer->setDelimiter(',')
            ->setEnclosure('"')
            ->setEscape('\\');

        $writer->insertOne(['from', 'to', 'original', 'missing_source']);
        foreach ($sourceArray as $row) {
            $writer->insertOne($row);
        }
    }
}
