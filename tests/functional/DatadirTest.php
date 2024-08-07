<?php

declare(strict_types=1);

namespace Keboola\ExTeradata\Tests\Functional;

use Dibi\Connection;
use Keboola\Component\JsonHelper;
use Keboola\DatadirTests\AbstractDatadirTestCase;
use Keboola\ExTeradata\Factories\ConnectionFactory;

class DatadirTest extends AbstractDatadirTestCase
{
    /** @var Connection */
    private $connection;

    public function setUp(): void
    {
        parent::setUp();
        $credentials = $this->getCredentials();
        $this->connection = (new ConnectionFactory())->create(
            $credentials['host'],
            $credentials['port'],
            $credentials['user'],
            $credentials['#password']
        );
    }

    private function getCredentials(): array
    {
        $requiredCredentials = ['TERADATA_HOST', 'TERADATA_USERNAME', 'TERADATA_PASSWORD', 'TERADATA_DATABASE'];
        foreach ($requiredCredentials as $requiredCredential) {
            if (empty(getenv($requiredCredential))) {
                throw new \Exception(sprintf(
                    'Variable \'%s\' must be set.',
                    $requiredCredential
                ));
            }
        }

        return [
            'host' => getenv('TERADATA_HOST'),
            'port' => (int) getenv('TERADATA_PORT'),
            'user' => getenv('TERADATA_USERNAME'),
            '#password' => getenv('TERADATA_PASSWORD'),
            'database' => getenv('TERADATA_DATABASE'),
        ];
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $database = 'ex_teradata_test';
        try {
            $this->connection->query('DELETE DATABASE ' . $database);
            $this->connection->query('DROP DATABASE ' . $database);
        } catch (\Throwable $exception) {
            if (!preg_match(
                '~Database \'(.+)\' does not exist. S0002~',
                $exception->getMessage()
            )) {
                throw $exception;
            }
        }
    }

    private function createDatabase(string $database): void
    {
        try {
            $sql = sprintf('CREATE DATABASE %s AS PERMANENT=1e9', $database);
            $this->connection->query($sql);
        } catch (\Throwable $exception) {
            print $exception->getMessage();
        }
    }

    private function createTable(string $database, string $table): void
    {
        try {
            $sql = "CREATE TABLE $database.$table (column1 VARCHAR (32), column2 INTEGER)";
            $this->connection->query($sql);
        } catch (\Throwable $exception) {
            print $exception->getMessage();
        }
    }

    private function insertBasicData(string $database, string $table): void
    {
        try {
            $sql = "INSERT INTO $database.$table  VALUES ('row1', 1)";
            $this->connection->query($sql);

            $sql = "INSERT INTO $database.$table  VALUES ('row2', 2)";
            $this->connection->query($sql);
        } catch (\Throwable $exception) {
            print $exception->getMessage();
        }
    }

    private function insertAggregatedBasicData(string $database, string $table): void
    {
        try {
            $sql = "INSERT INTO $database.$table  VALUES ('row1', 1)";
            $this->connection->query($sql);

            $sql = "INSERT INTO $database.$table  VALUES ('row2', 2)";
            $this->connection->query($sql);

            $sql = "INSERT INTO $database.$table  VALUES ('row3', 1)";
            $this->connection->query($sql);

            $sql = "INSERT INTO $database.$table  VALUES ('row4', 1)";
            $this->connection->query($sql);
        } catch (\Throwable $exception) {
            print $exception->getMessage();
        }
    }

    private function getConfig(string $dataDir, array $customDbNode = []): array
    {
        $configuration = JsonHelper::readFile($dataDir . '/config.json');
        $configuration['parameters']['db'] = array_merge($this->getCredentials(), $customDbNode);
        return $configuration;
    }

    public function testActionGetTables(): void
    {
        $dataDir = __DIR__ . '/get-tables';
        $configuration = $this->getConfig($dataDir);
        $database = $configuration['parameters']['db']['database'];
        $table = 'test_1';

        $this->createDatabase($database);
        $this->createTable($database, $table);
        $this->insertBasicData($database, $table);

        $response = [
            'status' => 'success',
            'tables' => [
                [
                    'schema' => 'ex_teradata_test',
                    'name' => 'test_1',
                    'columns' => [
                        ['name' => 'column1'],
                        ['name' => 'column2'],
                    ],
                ],
            ],
        ];

        $this->runTestWithCustomConfiguration(
            $dataDir,
            $configuration,
            0,
            JsonHelper::encode($response),
            null
        );
    }

    public function testInvalidHostname(): void
    {
        $dataDir = __DIR__ . '/empty-data';
        $configuration = $this->getConfig($dataDir, ['host' => 'invalid_hostname']);
        $configuration['parameters']['outputTable'] = 'test_1';
        $configuration['parameters']['query'] = 'SELECT 1';
        $database = $configuration['parameters']['db']['database'];
        $table = 'test_1';

        $this->createDatabase($database);
        $this->createTable($database, $table);
        $this->insertBasicData($database, $table);

        $this->runTestWithCustomConfiguration(
            $dataDir,
            $configuration,
            1,
            null,
            'The Teradata server can\'t currently be reached over this network.' . PHP_EOL
        );
    }

    public function testInvalidUser(): void
    {
        $dataDir = __DIR__ . '/empty-data';
        $configuration = $this->getConfig($dataDir, ['user' => 'invalid_user']);
        $configuration['parameters']['outputTable'] = 'test_1';
        $configuration['parameters']['query'] = 'SELECT 1';
        $database = $configuration['parameters']['db']['database'];
        $table = 'test_1';

        $this->createDatabase($database);
        $this->createTable($database, $table);
        $this->insertBasicData($database, $table);

        $this->runTestWithCustomConfiguration(
            $dataDir,
            $configuration,
            1,
            null,
            'The User or Password is invalid.' . PHP_EOL
        );
    }

    public function testInvalidPassword(): void
    {
        $dataDir = __DIR__ . '/empty-data';
        $configuration = $this->getConfig($dataDir, ['#password' => 'invalid_password']);
        $configuration['parameters']['outputTable'] = 'test_1';
        $configuration['parameters']['query'] = 'SELECT 1';
        $database = $configuration['parameters']['db']['database'];
        $table = 'test_1';

        $this->createDatabase($database);
        $this->createTable($database, $table);
        $this->insertBasicData($database, $table);

        $this->runTestWithCustomConfiguration(
            $dataDir,
            $configuration,
            1,
            null,
            'The User or Password is invalid.' . PHP_EOL
        );
    }

    public function testWithoutCredentials(): void
    {
        $dataDir = __DIR__ . '/empty-data';
        $configuration = $this->getConfig($dataDir);
        unset($configuration['parameters']['db']);

        $this->runTestWithCustomConfiguration(
            $dataDir,
            $configuration,
            1,
            null,
            'The child node "db" at path "root.parameters" must be configured.' . PHP_EOL
        );
    }

    public function testWithoutSpecifiedTable(): void
    {
        $dataDir = __DIR__ . '/empty-data';
        $configuration = $this->getConfig($dataDir);
        $configuration['parameters']['outputTable'] = 'test';
        $database = $configuration['parameters']['db']['database'];
        $table = 'test_1';

        $this->createDatabase($database);
        $this->createTable($database, $table);
        $this->insertBasicData($database, $table);

        $this->runTestWithCustomConfiguration(
            $dataDir,
            $configuration,
            1,
            null,
            'Invalid configuration for path "root.parameters": The \'query\' or'
            . ' \'table.schema\' with \'table.tableName\' option is required.' . PHP_EOL
        );
    }

    public function testExtractAllFromBasicData(): void
    {
        $dataDir = __DIR__ . '/basic-data';
        $configuration = $this->getConfig($dataDir);
        $database = $configuration['parameters']['db']['database'];
        $table = 'test_1';

        $this->createDatabase($database);
        $this->createTable($database, $table);
        $this->insertBasicData($database, $table);

        $this->runTestWithCustomConfiguration(
            $dataDir,
            $configuration,
            0,
            'Extracted table into: "out.c-main.test-1".' . PHP_EOL,
            null
        );
    }

    public function testExtractEmptyDataWithRestrictedCharacterInDatabaseName(): void
    {
        $dataDir = __DIR__ . '/empty-data';
        $configuration = $this->getConfig($dataDir);
        $configuration['parameters']['outputTable'] = 'test_1';
        $configuration['parameters']['table'] = [
            'schema' => 'database"_name',
            'tableName' => 'test_1',
        ];
        $database = $configuration['parameters']['db']['database'];
        $table = 'test_1';

        $this->createDatabase($database);
        $this->createTable($database, $table);
        $this->insertBasicData($database, $table);

        $this->runTestWithCustomConfiguration(
            $dataDir,
            $configuration,
            1,
            null,
            'Object "database"_name" contain restricted character \'"\'.' . PHP_EOL
        );
    }

    public function testExtractEmptyDataWithRestrictedCharacterInTableName(): void
    {
        $dataDir = __DIR__ . '/empty-data';
        $configuration = $this->getConfig($dataDir);
        $configuration['parameters']['outputTable'] = 'test_1';
        $configuration['parameters']['table'] = [
            'schema' => 'ex_teradata_test',
            'tableName' => 'te"st_1',
        ];
        $database = $configuration['parameters']['db']['database'];
        $table = 'test_1';

        $this->createDatabase($database);
        $this->createTable($database, $table);
        $this->insertBasicData($database, $table);

        $this->runTestWithCustomConfiguration(
            $dataDir,
            $configuration,
            1,
            null,
            'Object "te"st_1" contain restricted character \'"\'.' . PHP_EOL
        );
    }

    public function testExtractEmptyDataWithRestrictedCharacterInColumnName(): void
    {
        $dataDir = __DIR__ . '/empty-data';
        $configuration = $this->getConfig($dataDir);
        $configuration['parameters']['outputTable'] = 'test_1';
        $configuration['parameters']['table'] = [
            'schema' => 'ex_teradata_test',
            'tableName' => 'test_1',
        ];
        $configuration['parameters']['columns'] = ['col"umn1'];
        $database = $configuration['parameters']['db']['database'];
        $table = 'test_1';

        $this->createDatabase($database);
        $this->createTable($database, $table);
        $this->insertBasicData($database, $table);

        $this->runTestWithCustomConfiguration(
            $dataDir,
            $configuration,
            1,
            null,
            'Object "col"umn1" contain restricted character \'"\'.' . PHP_EOL
        );
    }

    public function testExtractColumn1FromBasicData(): void
    {
        $dataDir = __DIR__ . '/basic-data-export-one-column';
        $configuration = $this->getConfig($dataDir);
        $database = $configuration['parameters']['db']['database'];
        $table = 'test_1';

        $this->createDatabase($database);
        $this->createTable($database, $table);
        $this->insertBasicData($database, $table);

        $this->runTestWithCustomConfiguration(
            $dataDir,
            $configuration,
            0,
            'Extracted table into: "out.c-main.test-1".' . PHP_EOL,
            null
        );
    }

    public function testExtractWithUserSql(): void
    {
        $dataDir = __DIR__ . '/aggregated-data';
        $configuration = $this->getConfig($dataDir);
        $database = $configuration['parameters']['db']['database'];
        $table = 'test_2';

        $this->createDatabase($database);
        $this->createTable($database, $table);
        $this->insertAggregatedBasicData($database, $table);

        $this->runTestWithCustomConfiguration(
            $dataDir,
            $configuration,
            0,
            'Extracted table into: "out.c-main.test-2".' . PHP_EOL,
            null
        );
    }

    public function testExtractFromNonExistingDatabase(): void
    {
        $dataDir = __DIR__ . '/empty-data';
        $configuration = $this->getConfig($dataDir);
        $configuration['parameters']['outputTable'] = 'invalid_database';
        $configuration['parameters']['table'] = [
            'schema' => 'invalid_database',
            'tableName' => 'test_1',
        ];
        $database = $configuration['parameters']['db']['database'];
        $table = 'test_1';

        $this->createDatabase($database);
        $this->createTable($database, $table);
        $this->insertBasicData($database, $table);

        $this->runTestWithCustomConfiguration(
            $dataDir,
            $configuration,
            1,
            null,
            'Database "invalid_database" does not exist.' . PHP_EOL
        );
    }

    public function testExtractFromNonExistingTable(): void
    {
        $dataDir = __DIR__ . '/empty-data';
        $configuration = $this->getConfig($dataDir);
        $configuration['parameters']['outputTable'] = 'invalid_table';
        $configuration['parameters']['table'] = [
            'schema' => 'ex_teradata_test',
            'tableName' => 'invalid_table',
        ];
        $database = $configuration['parameters']['db']['database'];
        $table = 'test_1';

        $this->createDatabase($database);
        $this->createTable($database, $table);
        $this->insertBasicData($database, $table);

        $this->runTestWithCustomConfiguration(
            $dataDir,
            $configuration,
            1,
            null,
            'Table "invalid_table" does not exist in database "ex_teradata_test".' . PHP_EOL
        );
    }

    public function testExtractEmptyTable(): void
    {
        $dataDir = __DIR__ . '/empty-table';
        $configuration = $this->getConfig($dataDir);
        $database = $configuration['parameters']['db']['database'];
        $table = 'test_1';

        $this->createDatabase($database);
        $this->createTable($database, $table);

        $this->runTestWithCustomConfiguration(
            $dataDir,
            $configuration,
            0,
            'Extracted table into: "out.c-main.test-1".' . PHP_EOL,
            null
        );
    }

    public function testExtractTableWithByteColumn(): void
    {
        $dataDir = __DIR__ . '/basic-data-byte-column';
        $configuration = $this->getConfig($dataDir, ['database' => 'DBC']);

        $this->runTestWithCustomConfiguration(
            $dataDir,
            $configuration,
            1,
            null,
            'You are probably trying to export one or more columns with data type "byte"'
            . ' which is not allowed.' . PHP_EOL
        );
    }
}
