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
use Throwable;

class ActionComponent extends BaseComponent
{
    private function getConnection(): Connection
    {
        /** @var Config $config */
        $config = $this->getConfig();

        return (new ConnectionFactory())->create(
            $config->getHost(),
            $config->getPort(),
            $config->getUser(),
            $config->getPassword(),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function getSyncActions(): array
    {
        return [
            'testConnection' => 'testConnection',
            'getTables' => 'getTables',
        ];
    }

    protected function getConfigClass(): string
    {
        return Config::class;
    }

    protected function getConfigDefinitionClass(): string
    {
        return ConfigDefinition::class;
    }

    /**
     * @return array{status: string}
     */
    public function testConnection(): array
    {
        $this->getConnection()->query('SELECT 1');
        return ['status' => 'success'];
    }

    /**
     * @return array{status: string, tables: Table[]}
     */
    public function getTables(): array
    {
        $connection = $this->getConnection();
        $config = $this->getConfig();
        assert($config instanceof Config);
        $database = $config->getDatabase();
        return [
            'status' => 'success',
            'tables' => $this->getTablesResponse($connection, $database),
        ];
    }

    /**
     * @return Table[]
     */
    private function getTablesResponse(Connection $connection, string $database): array
    {
        $sql = 'SELECT TableName, ColumnName FROM DBC.ColumnsV
WHERE DatabaseName = ?
ORDER BY TableName, ColumnName';

        try {
            $rows = $connection->query($sql, $database)->fetchAll();
        } catch (Throwable $exception) {
            throw (new ExceptionHandler())->createException($exception);
        }

        /** @var Table[] $tables */
        $tables = [];
        foreach ($rows as $row) {
            $tableName = $row['TableName'];
            if (!isset($tables[$tableName])) {
                $tables[$tableName] = new Table($database, $tableName);
            }
            $tables[$tableName]->addColumn(new Column($row['ColumnName']));
        }

        return array_values($tables);
    }
}
