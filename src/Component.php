<?php

declare(strict_types=1);

namespace Keboola\ExTeradata;

use Dibi\Connection;
use Keboola\Component\BaseComponent;

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
        /** @var Config $config */
        $config = $this->getConfig();

        $exceptionHandler = new ExceptionHandler();

        try {
            $connection = $this->createConnection(
                $config->getHost(),
                $config->getUser(),
                $config->getPassword()
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

        $query = $config->getQuery() ?? $extractorHelper->getExportSql(
            $config->getSchema(),
            $config->getTableName(),
            $config->getColumns()
        );
        $outputCsvFilePath = $this->getDataDir() . '/out/tables/' . $config->getOutputTable() . '.csv';

        $extractor->extractTable($query, $outputCsvFilePath);

        $this->getLogger()->info(sprintf('Extracted table: "%s".', $config->getName()));
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
