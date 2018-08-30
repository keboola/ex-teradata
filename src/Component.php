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

        $credentials = $parameters['db'] ?? null;
        if ($credentials === null) {
            throw new UserException('Database credentials must be set.');
        }

        if (!isset($parameters['query']) && !isset($parameters['table']['tableName'])) {
            throw new UserException('Table name must be set in configuration.');
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

        $query = $parameters['query'] ?? $this->getExportSql(
            $credentials['database'],
            $parameters['table']['tableName'],
            $parameters['columns']
        );
        $outputCsvFilePath = $this->getDataDir() . '/out/tables/' . $parameters['outputTable'] . '.csv';

        $extractor = new Extractor($connection, $exceptionHandler);
        $extractor->extractTable($query, $outputCsvFilePath);

        $this->getLogger()->info(sprintf('Extracted table: "%s".', $parameters['name']));
    }

    private function getExportSql(string $database, string $tableName, ?array $columns): string
    {
        if ($columns) {
            $objects = implode(',', $columns);
        } else {
            $objects = '*';
        }

        return sprintf('SELECT %s FROM %s.%s', $objects, $database, $tableName);
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
