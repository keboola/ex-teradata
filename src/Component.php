<?php

declare(strict_types=1);

namespace Keboola\ExTeradata;

use Dibi\Connection;
use Keboola\Component\BaseComponent;
use Keboola\Component\UserException;
use Keboola\Csv\CsvWriter;

class Component extends BaseComponent
{
    private function createConnection(string $host, string $usename, string $password): Connection
    {
        return new Connection([
            'dsn' => sprintf('DRIVER={Teradata};DBCName=%s', $host),
            'driver'   => 'odbc',
            'username' => $usename,
            'password' => $password,
        ]);
    }

    /**
     * @param string $outputFilePath
     * @param \Dibi\Row[] $data
     *
     * @throws \Keboola\Csv\Exception
     * @throws \Keboola\Csv\InvalidArgumentException
     */
    private function writeDataToCsv(string $outputFilePath, array $data): void
    {
        $csvWriter = new CsvWriter(
            $outputFilePath,
            CsvWriter::DEFAULT_DELIMITER,
            CsvWriter::DEFAULT_ENCLOSURE,
            "\r\n"
        );

        $columns = [];
        foreach ($data[0] as $columnName => $value) {
            $columns[] = $columnName;
        }

        $csvWriter->writeRow($columns);

        foreach ($data as $rowData) {
            $row = [];
            foreach ($columns as $column) {
                $row[] = $rowData[$column];
            }

            $csvWriter->writeRow($row);
        }
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

        try {
            $connection = $this->createConnection(
                $credentials['host'],
                $credentials['username'],
                $credentials['#password']
            );
        } catch (\Throwable $exception) {
            if (preg_match(
                '~The Teradata server can\'t currently be reached over this network~',
                $exception->getMessage()
            )) {
                throw new UserException('The Teradata server can\'t currently be reached over this network.');
            } elseif (preg_match(
                '~The UserId, Password or Account is invalid.~',
                $exception->getMessage()
            )) {
                throw new UserException('The Username or Password is invalid.');
            } else {
                throw $exception;
            }
        }

        $extractor = new Extractor($connection, $credentials['database']);

        $exportedTables = [];
        foreach ($tables as $table) {
            $outputFilePath = $this->getDataDir() . '/out/tables/' . $table['outputTable'] . '.csv';

            $data = $extractor->extractTable($table['name']);
            $this->writeDataToCsv($outputFilePath, $data);

            $exportedTables[] = $table['name'];
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
