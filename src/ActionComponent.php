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
                $config->getUser(),
                $config->getPassword()
            );
        } catch (\Throwable $exception) {
            throw $exceptionHandler->createException($exception);
        }

        switch ($config->getAction()) {
            case 'testConnection':
                $this->testConnection($connection);
                print json_encode(['status' => 'ok'], JSON_PRETTY_PRINT);
                break;
            case 'getTables':
                print json_encode(
                    [
                        'status' => 'ok',
                        'tables' => $this->getTables($connection, $config->getDatabase()),
                    ],
                    JSON_PRETTY_PRINT
                );
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
    }

    private function getTables(Connection $connection, string $database): array
    {
        $tables = $connection->query("SELECT * FROM dbc.tables WHERE DatabaseName='{$database}'")->fetchAll();
        $columns = $connection->query("SELECT * FROM dbc.columns WHERE DatabaseName='{$database}'")->fetchAll();

        $tableReseponse = [];
        foreach ($tables as $table) {
            $tableReseponse[] = new Table(
                trim($table['DatabaseName']),
                trim($table['TableName']),
                $this->getTableColumns(trim($table['TableName']), $columns)
            );
        }

        return $tableReseponse;
    }

    private function getTableColumns(string $table, array $columns): array
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
