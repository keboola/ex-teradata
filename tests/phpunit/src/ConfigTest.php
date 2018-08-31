<?php

declare(strict_types=1);

namespace Keboola\ExTeradata\Tests\Unit;

use Keboola\ExTeradata\Config;
use Keboola\ExTeradata\ConfigDefinition;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /** @var ConfigDefinition */
    private $configDefinition;

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
                'name' => 'tableName',
                'query' => 'SELECT a FROM b',
                'outputTable' => 'outputFile',
            ],
        ];

        $config = new Config(
            $parameters,
            $this->configDefinition
        );

        $this->assertEquals('hostname', $config->getHost());
        $this->assertEquals('username', $config->getUser());
        $this->assertEquals('password', $config->getPassword());
        $this->assertEquals('tableName', $config->getName());
        $this->assertEquals('SELECT a FROM b', $config->getQuery());
        $this->assertEquals('outputFile', $config->getOutputTable());
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
                    'user' => 'username',
                    '#password' => 'password',
                    'database' => 'database',
                ],
                'name' => 'name',
                'outputTable' => 'outputFile',
                'table' => [
                    'schema' => 'schema',
                    'tableName' => 'tableName',
                ],
            ],
        ];

        $config = new Config(
            $parameters,
            $this->configDefinition
        );

        $this->assertEquals('hostname', $config->getHost());
        $this->assertEquals('username', $config->getUser());
        $this->assertEquals('password', $config->getPassword());
        $this->assertEquals('name', $config->getName());
        $this->assertNull($config->getQuery());
        $this->assertEquals('outputFile', $config->getOutputTable());
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
                'name' => 'name',
                'outputTable' => 'outputFile',
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
            $this->configDefinition
        );

        $this->assertEquals('hostname', $config->getHost());
        $this->assertEquals('username', $config->getUser());
        $this->assertEquals('password', $config->getPassword());
        $this->assertEquals('name', $config->getName());
        $this->assertNull($config->getQuery());
        $this->assertEquals('outputFile', $config->getOutputTable());
        $this->assertEquals('schema', $config->getSchema());
        $this->assertEquals('tableName', $config->getTableName());

        $this->assertCount(2, $config->getColumns());
        $this->assertEquals('column1', $config->getColumns()[0]);
        $this->assertEquals('column2', $config->getColumns()[1]);
    }
}
