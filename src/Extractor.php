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

    /** @var array */
    private $tableColumns = [];

    public function __construct(
        Connection $connection,
        CsvWriterFactory $csvWriterFactory,
        ExceptionHandler $exceptionHandler
    ) {
        $this->connection = $connection;
        $this->csvWriterFactory = $csvWriterFactory;
        $this->exceptionHandler = $exceptionHandler;
    }

    private function getTableColumns(): array
    {
        if (empty($this->tableColumns)) {
            throw new UserException('Table has no columns.');
        }
        return $this->tableColumns;
    }

    private function setTableColumns(array $columns): void
    {
        $this->tableColumns = $columns;
    }

    private function createCsvWriter(string $outputCsvFilePath): CsvWriter
    {
        return new CsvWriter(
            $outputCsvFilePath,
            CsvWriter::DEFAULT_DELIMITER,
            CsvWriter::DEFAULT_ENCLOSURE,
            "\r\n"
        );
    }

    public function getExportSql(string $database, string $tableName, ?array $columns): string
    {
        if ($columns) {
            $objects = implode(',', $columns);
        } else {
            $objects = '*';
        }

        return sprintf('SELECT %s FROM %s.%s', $objects, $database, $tableName);
    }

    public function extractTable(string $query, string $outputCsvFilePath): void
    {
        try {
            $queryResult = $this->connection->nativeQuery($query);
        } catch (\Throwable $exception) {
            $this->exceptionHandler->handleException($exception);
            throw new \RuntimeException();
        }

        $csvWriter = $this->csvWriterFactory->create($outputCsvFilePath);
        $counter = 0;
        /** @var Row $tableRow */
        foreach ($this->fetchTableRows($queryResult) as $tableRow) {
            if ($counter === 0) {
                $this->setTableColumns(array_keys($tableRow->toArray()));
                $csvWriter->writeRow($this->getTableColumns());
            }

            $csvWriter->writeRow($tableRow->toArray());
            $counter++;
        }

        if ($counter === 0) {
            throw new \Exception('Empty export');
        }
    }

    public function fetchTableRows(Result $queryResult): \Iterator
    {
        try {
            while ($row = $queryResult->fetch()) {
                yield $row;
            }
        } catch (\Throwable $exception) {
            $this->exceptionHandler->handleException($exception);
            throw new \RuntimeException();
        }
    }
}
