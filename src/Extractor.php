<?php

declare(strict_types=1);

namespace Keboola\ExTeradata;

use Dibi\Connection;
use Dibi\Result;
use Keboola\Component\UserException;
use Keboola\Csv\CsvWriter;

class Extractor
{
    /** @var Connection */
    private $connection;

    /** @var ExceptionHandler */
    private $exceptionHandler;

    /** @var string */
    private $dataDir;

    /** @var string */
    private $database;

    /** @var array */
    private $tableColumns;

    public function __construct(Connection $connection, ExceptionHandler $exceptionHandler, string $dataDir, string $database)
    {
        $this->connection = $connection;
        $this->exceptionHandler = $exceptionHandler;
        $this->dataDir = $dataDir;
        $this->database = $database;
    }

    private function getTableColumns(): array
    {
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

    private function getExportSql(string $tableName, ?array $columns): string
    {
        if ($columns) {
            $columnNames = array_map(
                function ($column) {
                    return $column['name'];
                },
                $columns
            );
            $objects = implode(',', $columnNames);
        } else {
            $objects = '*';
        }

        return sprintf('SELECT %s FROM %s.%s', $objects, $this->database, $tableName);
    }

    public function extractTable(array $tableConfig): void
    {
        $tableName = $tableConfig['name'];
        $outputCsvFilePath = $this->dataDir . '/out/tables/' . $tableConfig['outputTable'] . '.csv';
        $sql = $tableConfig['query'] ?? $this->getExportSql($tableConfig['name'], $tableConfig['columns']);

        try {
            $queryResult = $this->connection->query($sql);
        } catch (\Throwable $exception) {
            $this->exceptionHandler->handleException($exception, $this->database, $tableName);
            throw new \RuntimeException();
        }

        $csvWriter = $this->createCsvWriter($outputCsvFilePath);
        $counter = 0;
        foreach ($this->fetchTableRows($queryResult, $tableName) as $tableRow) {
            if ($counter === 0) {

                $columns = [];
                foreach ($tableRow as $columnName => $value) {
                    $columns[] = $columnName;
                }

                $this->setTableColumns($columns);
                if (!empty($columns)) {
                    $csvWriter->writeRow($columns);
                } else {
                    throw new UserException('Table has no columns.');
                }
            }

            $row = [];
            foreach ($this->getTableColumns() as $column) {
                $row[] = $tableRow[$column];
            }

            $csvWriter->writeRow($row);
            $counter++;
        }

        if ($counter === 0) {
            throw new \Exception('Empty export');
        }
    }

    public function fetchTableRows(Result $queryResult , string $tableName): \Iterator
    {
        try {
            while ($row = $queryResult->fetch()) {
                yield $row;
            }
        } catch (\Throwable $exception) {
            $this->exceptionHandler->handleException($exception, $this->database, $tableName);
            throw new \RuntimeException();
        }
    }
}
