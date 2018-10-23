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
        $sql = "SELECT tab.TVMName TableName, col.FieldName ColumnName FROM DBC.TVFields col
JOIN DBC.TVM tab
ON tab.TVMId = col.TableId
JOIN DBC.Dbase db
ON db.DatabaseId = tab.DatabaseId
WHERE db.DatabaseName = ?
ORDER BY TableName, ColumnName";
        $rows = $connection->query($sql, $database)->fetchAll();

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
