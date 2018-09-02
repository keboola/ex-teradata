<?php

declare(strict_types=1);

namespace Keboola\ExTeradata;

use Dibi\Connection;
use Dibi\Result;
use Dibi\Row;
use Keboola\Component\UserException;

class Extractor
{
    /** @var Connection */
    private $connection;

    /** @var CsvWriterFactory */
    private $csvWriterFactory;

    /** @var ExceptionHandler */
    private $exceptionHandler;

    public function __construct(
        Connection $connection,
        CsvWriterFactory $csvWriterFactory,
        ExceptionHandler $exceptionHandler
    ) {
        $this->connection = $connection;
        $this->csvWriterFactory = $csvWriterFactory;
        $this->exceptionHandler = $exceptionHandler;
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

        foreach ($this->fetchTableRows($queryResult) as $tableRow) {
            $csvWriter->writeRow($tableRow->toArray());
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
