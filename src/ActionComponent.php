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
    public function run(): void
    {
        /** @var Config $config */
        $config = $this->getConfig();

        $connection = (new ConnectionFactory())->create(
            $config->getHost(),
            $config->getPort(),
            $config->getUser(),
            $config->getPassword(),
        );

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
        $connection->query('SELECT 1');
        print json_encode(['status' => 'success'], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
    }

    private function getTables(Connection $connection, string $database): void
    {
        print json_encode([
            'status' => 'success',
            'tables' => $this->getTablesResponse($connection, $database),
        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
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
