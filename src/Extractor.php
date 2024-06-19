<?php

declare(strict_types=1);

namespace Keboola\ExTeradata;

use Dibi\Connection;
use Dibi\Result;
use Dibi\Row;
use Iterator;
use Keboola\ExTeradata\Factories\CsvWriterFactory;
use Psr\Log\LoggerInterface;
use Throwable;

class Extractor
{
    public function __construct(
        private Connection $connection,
        private CsvWriterFactory $csvWriterFactory,
        private ExceptionHandler $exceptionHandler,
        private LoggerInterface $logger,
    ) {
    }

    public function extractTable(string $query, string $outputCsvFilePath): void
    {
        try {
            $queryResult = $this->connection->nativeQuery($query);
        } catch (Throwable $exception) {
            throw $this->exceptionHandler->createException($exception);
        }

        $csvWriter = $this->csvWriterFactory->create($outputCsvFilePath);
        $csvWriter->writeRow($queryResult->getInfo()->getColumnNames());

        $rowNumber = 0;
        foreach ($this->fetchTableRows($queryResult) as $tableRow) {
            $csvWriter->writeRow($tableRow->toArray());

            if ($rowNumber > 0 && $rowNumber % 1000000 === 0) {
                $this->logger->info(sprintf('%s queries fetched.', $rowNumber));
            }
            $rowNumber++;
        }
    }

    /**
     *
     * @return \Iterator|Row[]
     * @throws \Throwable
     */
    public function fetchTableRows(Result $queryResult): Iterator
    {
        try {
            while ($row = $queryResult->fetch()) {
                assert($row instanceof Row);
                yield $row;
            }
        } catch (Throwable $exception) {
            throw $this->exceptionHandler->createException($exception);
        }
    }
}
