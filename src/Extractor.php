<?php

declare(strict_types=1);

namespace Keboola\ExTeradata;

use Dibi\Connection;
use Keboola\Csv\CsvWriter;

class Extractor
{
    /** @var Connection */
    private $connection;

    /** @var ExceptionHandler */
    private $exceptionHandler;

    /** @var string */
    private $database;

    /** @var CsvWriter */
    private $csvWriter;

    /** @var array */
    private $tableColumns;

    public function __construct(Connection $connection, ExceptionHandler $exceptionHandler, string $database)
    {
        $this->connection = $connection;
        $this->exceptionHandler = $exceptionHandler;
        $this->database = $database;
    }

    private function getCsvWriter(): CsvWriter
    {
        return $this->csvWriter;
    }

    private function setCsvWriter(CsvWriter $csvWriter): void
    {
        $this->csvWriter = $csvWriter;
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

    private function getExportSql(string $tableName): string
    {
        return sprintf('SELECT * FROM %s.%s', $this->database, $tableName);
    }

    public function extractTable(string $tableName, string $outputCsvFilePath, ?string $sql): void
    {
        if ($sql === null) {
            $sql = $this->getExportSql($tableName);
        }
        $counter = 0;
        foreach ($this->fetchTableRows($tableName, $sql) as $tableRow) {
            if ($counter === 0) {
                $this->setCsvWriter($this->createCsvWriter($outputCsvFilePath));

                $columns = [];
                foreach ($tableRow as $columnName => $value) {
                    $columns[] = $columnName;
                }

                $this->setTableColumns($columns);
                if (!empty($columns)) {
                    $this->getCsvWriter()->writeRow($columns);
                }
            }

            $row = [];
            foreach ($this->getTableColumns() as $column) {
                $row[] = $tableRow[$column];
            }

            $this->getCsvWriter()->writeRow($row);
            $counter++;
        }

        if ($counter === 0) {
            throw new \Exception('Empty export');
        }
    }

    public function fetchTableRows(string $tableName, string $sql): \Iterator
    {
        try {
            $sth = $this->connection->query($sql);
            while ($row = $sth->fetch()) {
                yield $row;
            }
        } catch (\Throwable $exception) {
            $this->exceptionHandler->handleException($exception, $this->database, $tableName);
            throw new \RuntimeException();
        }
    }
}
