<?php

namespace EcomDev\TranslateTool\Command\MagentoOne;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use League\Csv\Writer;
use League\Csv\Reader;


class CsvCompare extends Command
{
    public function configure()
    {
        $this->setName('csv-compare');
        $this->setDescription('Compares CSV files');
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

        $this->addArgument('old-file', InputArgument::REQUIRED, 'Old csv file');
        $this->addArgument('new-file', InputArgument::REQUIRED, 'New csv file');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sourceReader = Reader::createFromPath($input->getArgument('old-file'));
        $sourceColumns = $sourceReader->fetchOne(0);
        $sourceColumnsCount = count($sourceColumns);
        $targetReader = Reader::createFromPath($input->getArgument('new-file'));
        $targetColumns = $targetReader->fetchOne(0);
        $targetColumnsCount = count($targetColumns);

        $filter = function ($row, $rowIndex) {
            return is_array($row) && count($row) > 3 && $rowIndex > 0;
        };

        $mapSource = function (array $row) use ($sourceColumns, $sourceColumnsCount) {
            if ($sourceColumnsCount != count($row)) {
                $row = array_slice(array_pad($row, $sourceColumnsCount, null), 0, $sourceColumnsCount);
            }

            return array_combine($sourceColumns, $row);
        };

        $mapTarget = function (array $row) use ($targetColumns, $targetColumnsCount) {
            if ($targetColumnsCount != count($row)) {
                $row = array_slice(array_pad($row, $targetColumnsCount, null), 0, $targetColumnsCount);
            }

            return array_combine($targetColumns, $row);
        };

        $sourceReader->addFilter($filter);
        $targetReader->addFilter($filter);

        $writer = Writer::createFromPath(
            $input->getOption('output') . DIRECTORY_SEPARATOR . $input->getOption('file-name'),
            'w'
        );

        $existingItems = [];
        foreach ($sourceReader->query($mapSource) as $row) {
            $existingItems[$row['phrase']] = $row['translation'];
        }

        $writer->setDelimiter(',')
            ->setEnclosure('"')
            ->setEscape('\\');

        $writer->insertOne(['scope', 'phrase', 'translation', 'ignore(y/n)', 'file where found']);

        foreach ($targetReader->query($mapTarget) as $row) {
            if (!isset($existingItems[$row['phrase']]) && !empty($row['phrase'])) {
                $writer->insertOne([
                    $row['scope'], $row['phrase'], $row['translation'],
                    $row['ignore(y/n)'], $row['file where found']
                ]);
            }
        }
    }
}
