<?php

declare(strict_types=1);

namespace Keboola\ExTeradata\Tests\Unit;

use Keboola\ExTeradata\Config\CoreComponent\Config;
use Keboola\ExTeradata\Config\CoreComponent\ConfigDefinition;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    private ConfigDefinition $configDefinition;

    public function setUp(): void
    {
        parent::setUp();

        $this->configDefinition = new ConfigDefinition();
    }

    public function testValidConfigWithDefinedQuery(): void
    {
        $parameters = [
            'parameters' => [
                'db' => [
                    'host' => 'hostname',
                    'user' => 'username',
                    '#password' => 'password',
                    'database' => 'database',
                ],
                'query' => 'SELECT a FROM b',
                'outputTable' => 'outputFile',
                'incremental' => false,
                'primaryKey' => [],
            ],
        ];

        $config = new Config(
            $parameters,
            $this->configDefinition,
        );

        $this->assertEquals('hostname', $config->getHost());
        $this->assertEquals(1025, $config->getPort());
        $this->assertEquals('username', $config->getUser());
        $this->assertEquals('password', $config->getPassword());
        $this->assertEquals('SELECT a FROM b', $config->getQuery());
        $this->assertEquals('outputFile', $config->getOutputTable());
        $this->assertFalse($config->getIncremental());
        $this->assertEmpty($config->getPrimaryKey());
        $this->assertNull($config->getSchema());
        $this->assertNull($config->getTableName());
        $this->assertEmpty($config->getColumns());
    }

    public function testValidConfigWithDefinedTable(): void
    {
        $parameters = [
            'parameters' => [
                'db' => [
                    'host' => 'hostname',
                    'port' => 1100,
                    'user' => 'username',
                    '#password' => 'password',
                    'database' => 'database',
                ],
                'outputTable' => 'outputFile',
                'incremental' => true,
                'primaryKey' => ['column1'],
                'table' => [
                    'schema' => 'schema',
                    'tableName' => 'tableName',
                ],
            ],
        ];

        $config = new Config(
            $parameters,
            $this->configDefinition,
        );

        $this->assertEquals('hostname', $config->getHost());
        $this->assertEquals(1100, $config->getPort());
        $this->assertEquals('username', $config->getUser());
        $this->assertEquals('password', $config->getPassword());
        $this->assertNull($config->getQuery());
        $this->assertEquals('outputFile', $config->getOutputTable());
        $this->assertTrue($config->getIncremental());
        $this->assertCount(1, $config->getPrimaryKey());
        $this->assertEquals('column1', $config->getPrimaryKey()[0]);
        $this->assertEquals('schema', $config->getSchema());
        $this->assertEquals('tableName', $config->getTableName());
        $this->assertEmpty($config->getColumns());
    }

    public function testValidConfigWithDefinedTableAndColumns(): void
    {
        $parameters = [
            'parameters' => [
                'db' => [
                    'host' => 'hostname',
                    'user' => 'username',
                    '#password' => 'password',
                    'database' => 'database',
                ],
                'outputTable' => 'outputFile',
                'incremental' => false,
                'primaryKey' => [
                    'column1',
                    'column2',
                ],
                'table' => [
                    'schema' => 'schema',
                    'tableName' => 'tableName',
                ],
                'columns' => [
                    'column1',
                    'column2',
                ],
            ],
        ];

        $config = new Config(
            $parameters,
            $this->configDefinition,
        );

        $this->assertEquals('hostname', $config->getHost());
        $this->assertEquals('username', $config->getUser());
        $this->assertEquals('password', $config->getPassword());
        $this->assertNull($config->getQuery());
        $this->assertEquals('outputFile', $config->getOutputTable());
        $this->assertFalse($config->getIncremental());
        $this->assertCount(2, $config->getPrimaryKey());
        $this->assertEquals('column1', $config->getPrimaryKey()[0]);
        $this->assertEquals('column2', $config->getPrimaryKey()[1]);
        $this->assertEquals('schema', $config->getSchema());
        $this->assertEquals('tableName', $config->getTableName());

        $this->assertCount(2, $config->getColumns());
        $this->assertEquals('column1', $config->getColumns()[0]);
        $this->assertEquals('column2', $config->getColumns()[1]);
    }
}
