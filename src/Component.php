<?php

declare(strict_types=1);

namespace Keboola\ExTeradata;

use Dibi\Connection;
use Keboola\Component\BaseComponent;
use Keboola\Component\UserException;
use Keboola\Csv\CsvWriter;

class Component extends BaseComponent
{
    /** @var array */
    private $tableColumns = [];

    /** @var CsvWriter */
    private $csvWriter;

    private function setTableColumns(string $tableName, array $columns): void
    {
        $this->tableColumns[$tableName] = $columns;
    }

    private function getTableColumns(string $tableName): array
    {
        return $this->tableColumns[$tableName];
    }

    private function setCsvWriter(CsvWriter $csvWriter): void
    {
        $this->csvWriter = $csvWriter;
    }

    private function getCsvWriter(): CsvWriter
    {
        return $this->csvWriter;
    }

    private function createConnection(string $host, string $usename, string $password): Connection
    {
        return new Connection([
            'dsn' => sprintf('DRIVER={Teradata};DBCName=%s', $host),
            'driver'   => 'odbc',
            'username' => $usename,
            'password' => $password,
        ]);
    }

    public function run(): void
    {
        $parameters = $this->getConfig()->getParameters();

        $tables = $parameters['tables'] ?? null;
        if ($tables === null) {
            throw new UserException('No tables specified to extract.');
        }

        $credentials = $parameters['db'] ?? null;
        if ($credentials === null) {
            throw new UserException('Database credentials must be set.');
        }

        $exceptionHandler = new ExceptionHandler();

        try {
            $connection = $this->createConnection(
                $credentials['host'],
                $credentials['username'],
                $credentials['#password']
            );
        } catch (\Throwable $exception) {
            $exceptionHandler->handleException($exception);
        }

        $extractor = new Extractor($connection, $exceptionHandler, $credentials['database']);

        $exportedTables = [];
        foreach ($tables as $table) {
            $tableName = $table['name'];
            $outputFilePath = $this->getDataDir() . '/out/tables/' . $table['outputTable'] . '.csv';

            $counter = 0;
            foreach ($extractor->extractTable($tableName) as $tableRow) {
                if ($counter === 0) {
                    $this->setCsvWriter(
                        new CsvWriter(
                            $outputFilePath,
                            CsvWriter::DEFAULT_DELIMITER,
                            CsvWriter::DEFAULT_ENCLOSURE,
                            "\r\n"
                        )
                    );

                    $columns = [];
                    foreach ($tableRow as $columnName => $value) {
                        $columns[] = $columnName;
                    }

                    $this->setTableColumns($tableName, $columns);
                    if(!empty($columns)) {
                        $this->getCsvWriter()->writeRow($columns);
                    }
                }

                $row = [];
                foreach ($this->getTableColumns($tableName) as $column) {
                    $row[] = $tableRow[$column];
                }

                $this->getCsvWriter()->writeRow($row);
                $counter++;
            }

            if ($counter === 0) {
                throw new \Exception('Empty export');
            }

            $exportedTables[] = $tableName;
        }

        $this->getLogger()->info(sprintf('Extracted tables: "%s".', implode(', ', $exportedTables)));
    }

    protected function getConfigClass(): string
    {
        return Config::class;
    }

    protected function getConfigDefinitionClass(): string
    {
        return ConfigDefinition::class;
    }
}
