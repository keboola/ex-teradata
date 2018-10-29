<?php

declare(strict_types=1);

namespace Keboola\ExTeradata;

use Dibi\Connection;
use Dibi\Result;
use Dibi\Row;
use Keboola\ExTeradata\Factories\CsvWriterFactory;
use Psr\Log\LoggerInterface;

class Extractor
{
    /** @var Connection */
    private $connection;

    /** @var CsvWriterFactory */
    private $csvWriterFactory;

    /** @var ExceptionHandler */
    private $exceptionHandler;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        Connection $connection,
        CsvWriterFactory $csvWriterFactory,
        ExceptionHandler $exceptionHandler,
        LoggerInterface $logger
    ) {
        $this->connection = $connection;
        $this->csvWriterFactory = $csvWriterFactory;
        $this->exceptionHandler = $exceptionHandler;
        $this->logger = $logger;
    }

    public function extractTable(string $query, string $outputCsvFilePath): void
    {
        try {
            $queryResult = $this->connection->nativeQuery($query);
        } catch (\Throwable $exception) {
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
     * @param Result $queryResult
     *
     * @return \Iterator|Row[]
     * @throws \Throwable
     */
    public function fetchTableRows(Result $queryResult): \Iterator
    {
        try {
            while ($row = $queryResult->fetch()) {
                yield $row;
            }
        } catch (\Throwable $exception) {
            throw $this->exceptionHandler->createException($exception);
        }
    }
}
