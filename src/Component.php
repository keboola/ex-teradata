<?php

declare(strict_types=1);

namespace Keboola\ExTeradata;

use Dibi\Connection;
use Keboola\Component\BaseComponent;
use Keboola\Component\UserException;

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
            throw new \RuntimeException();
        }

        $extractor = new Extractor($connection, $exceptionHandler, $credentials['database']);

        $exportedTables = [];
        foreach ($tables as $table) {
            $tableName = $table['name'];
            $outputCsvFilePath = $this->getDataDir() . '/out/tables/' . $table['outputTable'] . '.csv';
            $extractor->extractTable($tableName, $outputCsvFilePath);

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
