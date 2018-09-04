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

    private function testConnection(Connection $connection): void
    {
        $connection->query("SELECT NOW()");
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


        if ($config->getAction() === 'testConnection') {
            $this->testConnection($connection);
            exit(0);
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

        $manifestFileName = $outputCsvFilePath . '.manifest';
        $manifestData = [
            'incremental' => $config->getIncremental(),
            'primary_key' => $config->getPrimaryKey(),
        ];
        file_put_contents($manifestFileName, json_encode($manifestData, JSON_PRETTY_PRINT));

        $this->getLogger()->info(sprintf('Extracted table into: "%s".', $config->getOutputTable()));
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
