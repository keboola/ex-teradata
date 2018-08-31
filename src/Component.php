<?php

declare(strict_types=1);

namespace Keboola\ExTeradata;

use Dibi\Connection;
use Keboola\Component\BaseComponent;
use Keboola\Component\UserException;

class Component extends BaseComponent
{
    private function createConnection(string $host, string $user, string $password): Connection
    {
        return new Connection([
            'dsn' => sprintf('DRIVER={Teradata};DBCName=%s', $host),
            'driver'   => 'odbc',
            'username' => $user,
            'password' => $password,
        ]);
    }

    public function run(): void
    {
        $parameters = $this->getConfig()->getParameters();

        $exceptionHandler = new ExceptionHandler();

        try {
            $connection = $this->createConnection(
                $parameters['db']['host'],
                $parameters['db']['user'],
                $parameters['db']['#password']
            );
        } catch (\Throwable $exception) {
            throw $exceptionHandler->createException($exception);
        }

        $extractorHelper = new ExtractorHelper();
        $extractor = new Extractor(
            $connection,
            new CsvWriterFactory(),
            $exceptionHandler
        );

        $query = $parameters['query'] ?? $extractorHelper->getExportSql(
            $parameters['db']['database'],
            $parameters['table']['tableName'],
            $parameters['columns']
        );
        $outputCsvFilePath = $this->getDataDir() . '/out/tables/' . $parameters['outputTable'] . '.csv';

        $extractor->extractTable($query, $outputCsvFilePath);

        $this->getLogger()->info(sprintf('Extracted table: "%s".', $parameters['name']));
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
