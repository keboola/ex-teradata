<?php

declare(strict_types=1);

namespace Keboola\ExTeradata;

use Dibi\Connection;
use Keboola\Component\BaseComponent;
use Keboola\ExTeradata\Config\ActionComponent\Config;
use Keboola\ExTeradata\Config\ActionComponent\ConfigDefinition;
use Keboola\ExTeradata\Factories\ConnectionFactory;
use Keboola\ExTeradata\Response\Column;
use Keboola\ExTeradata\Response\Table;

class ActionComponent extends BaseComponent
{
    public function run(): void
    {
        /** @var Config $config */
        $config = $this->getConfig();

        $exceptionHandler = new ExceptionHandler();

        try {
            $connection = (new ConnectionFactory())->create(
                $config->getHost(),
                $config->getPort(),
                $config->getUser(),
                $config->getPassword()
            );
        } catch (\Throwable $exception) {
            throw $exceptionHandler->createException($exception);
        }

        switch ($config->getAction()) {
            case 'testConnection':
                $this->testConnection($connection);
                break;
            case 'getTables':
                $this->getTables($connection, $config->getDatabase());
                break;
        }
    }

    protected function getConfigClass(): string
    {
        return Config::class;
    }

    protected function getConfigDefinitionClass(): string
    {
        return ConfigDefinition::class;
    }

    private function testConnection(Connection $connection): void
    {
        $connection->query("SELECT 1");
        print json_encode(['status' => 'success'], JSON_PRETTY_PRINT);
    }

    private function getTables(Connection $connection, string $database): void
    {
        print json_encode(
            [
                'status' => 'success',
                'tables' => $this->getTablesResponse($connection, $database),
            ],
            JSON_PRETTY_PRINT
        );
    }

    private function getTablesResponse(Connection $connection, string $database): array
    {
        $tables = $connection->query(
            "SELECT DatabaseName, TableName FROM dbc.tables WHERE DatabaseName=? ORDER BY TableName",
            $database
        )->fetchAll();
        $columns = $connection->query(
            "SELECT TableName, ColumnName FROM dbc.columns WHERE DatabaseName=? ORDER BY ColumnName",
            $database
        )->fetchAll();

        $tableResponse = [];
        foreach ($tables as $table) {
            $tableResponse[] = new Table(
                trim($table['DatabaseName']),
                trim($table['TableName']),
                $this->getTableColumnsResponse(trim($table['TableName']), $columns)
            );
        }

        return $tableResponse;
    }

    private function getTableColumnsResponse(string $table, array $columns): array
    {
        $columnsResponse = [];
        foreach ($columns as $column) {
            if (trim($column['TableName']) === $table) {
                $columnsResponse[] = new Column(
                    trim($column['ColumnName'])
                );
            }
        }
        return $columnsResponse;
    }
}
