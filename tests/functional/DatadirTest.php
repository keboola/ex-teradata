<?php

declare(strict_types=1);

namespace Keboola\ExTeradata\Tests\Functional;

use Dibi\Connection;
use Keboola\DatadirTests\AbstractDatadirTestCase;
use Keboola\DatadirTests\DatadirTestSpecification;
use Keboola\ExTeradata\Factories\ConnectionFactory;

class DatadirTest extends AbstractDatadirTestCase
{
    /** @var Connection */
    private $connection;

    public function setUp(): void
    {
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

    private function createTableVarchar(string $database, string $table): void
    {
        try {
            $sql = "CREATE TABLE $database.$table (column1 VARCHAR (255), column2 VARCHAR (255))";
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

    public function testActionGetTables(): void
    {
        $testDirectory = __DIR__ . '/get-tables';

        $configuration = json_decode((string) file_get_contents($testDirectory . '/config.json'), true);
        $credentials = $this->getCredentials();
        $database = $credentials['database'];
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

        $specification = new DatadirTestSpecification(
            $testDirectory . '/source/data',
            0,
            json_encode($response, JSON_PRETTY_PRINT),
            null,
            $testDirectory . '/expected/data/out'
        );
        $tempDatadir = $this->getTempDatadir($specification);

        $configuration['parameters']['db'] = $credentials;
        file_put_contents(
            $tempDatadir->getTmpFolder() . '/config.json',
            json_encode($configuration, JSON_PRETTY_PRINT)
        );
        $process = $this->runScript($tempDatadir->getTmpFolder());
        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }

    public function testInvalidHostname(): void
    {
        $testDirectory = __DIR__ . '/empty-data';

        $configuration = json_decode((string) file_get_contents($testDirectory . '/config.json'), true);
        $credentials = $this->getCredentials();
        $database = $credentials['database'];
        $table = 'test_1';

        $this->createDatabase($database);
        $this->createTable($database, $table);
        $this->insertBasicData($database, $table);

        $specification = new DatadirTestSpecification(
            $testDirectory . '/source/data',
            1,
            null,
            'The Teradata server can\'t currently be reached over this network.' . PHP_EOL,
            $testDirectory . '/expected/data/out'
        );
        $tempDatadir = $this->getTempDatadir($specification);

        $credentials['host'] = 'invalid_hostname';
        $configuration['parameters']['db'] = $credentials;
        file_put_contents(
            $tempDatadir->getTmpFolder() . '/config.json',
            json_encode($configuration, JSON_PRETTY_PRINT)
        );
        $process = $this->runScript($tempDatadir->getTmpFolder());
        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }

    public function testInvalidUser(): void
    {
        $testDirectory = __DIR__ . '/empty-data';

        $configuration = json_decode((string) file_get_contents($testDirectory . '/config.json'), true);
        $credentials = $this->getCredentials();
        $database = $credentials['database'];
        $table = 'test_1';

        $this->createDatabase($database);
        $this->createTable($database, $table);
        $this->insertBasicData($database, $table);

        $specification = new DatadirTestSpecification(
            $testDirectory . '/source/data',
            1,
            null,
            'The User or Password is invalid.' . PHP_EOL,
            $testDirectory . '/expected/data/out'
        );
        $tempDatadir = $this->getTempDatadir($specification);

        $credentials['user'] = 'invalid_user';
        $configuration['parameters']['db'] = $credentials;
        file_put_contents(
            $tempDatadir->getTmpFolder() . '/config.json',
            json_encode($configuration, JSON_PRETTY_PRINT)
        );
        $process = $this->runScript($tempDatadir->getTmpFolder());
        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }

    public function testInvalidPassword(): void
    {
        $testDirectory = __DIR__ . '/empty-data';

        $configuration = json_decode((string) file_get_contents($testDirectory . '/config.json'), true);
        $credentials = $this->getCredentials();
        $database = $credentials['database'];
        $table = 'test_1';

        $this->createDatabase($database);
        $this->createTable($database, $table);
        $this->insertBasicData($database, $table);

        $specification = new DatadirTestSpecification(
            $testDirectory . '/source/data',
            1,
            null,
            'The User or Password is invalid.' . PHP_EOL,
            $testDirectory . '/expected/data/out'
        );
        $tempDatadir = $this->getTempDatadir($specification);

        $credentials['#password'] = 'invalid_password';
        $configuration['parameters']['db'] = $credentials;
        file_put_contents(
            $tempDatadir->getTmpFolder() . '/config.json',
            json_encode($configuration, JSON_PRETTY_PRINT)
        );
        $process = $this->runScript($tempDatadir->getTmpFolder());
        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }

    public function testWithoutCredentials(): void
    {
        $testDirectory = __DIR__ . '/empty-data';

        $configuration = json_decode((string) file_get_contents($testDirectory . '/config.json'), true);
        $credentials = $this->getCredentials();
        $database = $credentials['database'];
        $table = 'test_1';

        $this->createDatabase($database);
        $this->createTable($database, $table);
        $this->insertBasicData($database, $table);

        $specification = new DatadirTestSpecification(
            $testDirectory . '/source/data',
            1,
            null,
            'The child node "db" at path "root.parameters" must be configured.' . PHP_EOL,
            $testDirectory . '/expected/data/out'
        );
        $tempDatadir = $this->getTempDatadir($specification);

        file_put_contents(
            $tempDatadir->getTmpFolder() . '/config.json',
            json_encode($configuration, JSON_PRETTY_PRINT)
        );
        $process = $this->runScript($tempDatadir->getTmpFolder());
        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }

    public function testWithoutSpecifiedTable(): void
    {
        $testDirectory = __DIR__ . '/empty-data';

        $configuration = json_decode((string) file_get_contents($testDirectory . '/config.json'), true);
        $credentials = $this->getCredentials();
        $database = $credentials['database'];
        $table = 'test_1';

        $this->createDatabase($database);
        $this->createTable($database, $table);
        $this->insertBasicData($database, $table);

        $specification = new DatadirTestSpecification(
            $testDirectory . '/source/data',
            1,
            null,
            'Invalid configuration for path "root.parameters": The \'query\' or'
                . ' \'table.schema\' with \'table.tableName\' option is required.' . PHP_EOL,
            $testDirectory . '/expected/data/out'
        );
        $tempDatadir = $this->getTempDatadir($specification);

        $configuration['parameters']['db'] = $credentials;
        $configuration['parameters']['query'] = null;
        $configuration['parameters']['table'] = [
            'schema' => 'ex_teradata_test',
        ];
        file_put_contents(
            $tempDatadir->getTmpFolder() . '/config.json',
            json_encode($configuration, JSON_PRETTY_PRINT)
        );
        $process = $this->runScript($tempDatadir->getTmpFolder());
        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }

    public function testExtractAllFromBasicData(): void
    {
        $testDirectory = __DIR__ . '/basic-data';

        $configuration = json_decode((string) file_get_contents($testDirectory . '/config.json'), true);
        $credentials = $this->getCredentials();
        $database = $credentials['database'];
        $table = 'test_1';

        $this->createDatabase($database);
        $this->createTable($database, $table);
        $this->insertBasicData($database, $table);

        $specification = new DatadirTestSpecification(
            $testDirectory . '/source/data',
            0,
            'Extracted table into: "out.c-main.test-1".' . PHP_EOL,
            null,
            $testDirectory . '/expected/data/out'
        );
        $tempDatadir = $this->getTempDatadir($specification);

        $configuration['parameters']['db'] = $credentials;
        file_put_contents(
            $tempDatadir->getTmpFolder() . '/config.json',
            json_encode($configuration, JSON_PRETTY_PRINT)
        );
        $process = $this->runScript($tempDatadir->getTmpFolder());
        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }

    public function testExtractTableCzechChars(): void
    {
        $testDirectory = __DIR__ . '/basic-data-czech-chars';

        $configuration = json_decode((string) file_get_contents($testDirectory . '/config.json'), true);
        $credentials = $this->getCredentials();

        $database = $credentials['database'];
        $table = 'czech_chars';

        $this->createDatabase($database);
        $this->createTable($database, $table);
        try {
            $sql = "INSERT INTO $database.$table  VALUES ('ěščřžýáíéůúďťň', 1)";
            $this->connection->query($sql);
        } catch (\Throwable $exception) {
            print $exception->getMessage();
        }

        $specification = new DatadirTestSpecification(
            $testDirectory . '/source/data',
            0,
            'Extracted table into: "out.c-main.test-1".' . PHP_EOL,
            null,
            $testDirectory . '/expected/data/out'
        );
        $tempDatadir = $this->getTempDatadir($specification);

        $configuration['parameters']['db'] = $credentials;
        file_put_contents(
            $tempDatadir->getTmpFolder() . '/config.json',
            json_encode($configuration, JSON_PRETTY_PRINT)
        );
        $process = $this->runScript($tempDatadir->getTmpFolder());
        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }

    public function testExtractTableEscaping(): void
    {
        $testDirectory = __DIR__ . '/basic-data-escaping';

        $configuration = json_decode((string) file_get_contents($testDirectory . '/config.json'), true);
        $credentials = $this->getCredentials();

        $database = $credentials['database'];
        $table = 'escaping';

        $this->createDatabase($database);
        $this->createTableVarchar($database, $table);

        try {
            $sql = "INSERT INTO $database.$table VALUES ('unicode characters', 'ľš čť žý áí éú äô ň')";
            $this->connection->query($sql);

            /* this tests for sure that the characters are properly understood as unicode by teradata:
                If they are inserted correctly, then č is gonna be converted to Č, if not, it's either
                gonna be left as is or converted to some garbage. */
            $sql = "INSERT INTO $database.$table VALUES ('unicode initcap', " .
                "(SELECT INITCAP(column2) FROM $database.$table WHERE column1 = 'unicode characters'))";
            $this->connection->query($sql);

            $sql = "INSERT INTO $database.$table VALUES ('line with enclosure', 'second column')";
            $this->connection->query($sql);

            $sql = "INSERT INTO $database.$table VALUES ('first', 'something with

double new line')";
            $this->connection->query($sql);

            $sql = "INSERT INTO $database.$table VALUES ('columns with
new line', 'columns with 	tab')";
            $this->connection->query($sql);

            $sql = "INSERT INTO $database.$table VALUES ('column with \n \t \\',
 'second col')";
            $this->connection->query($sql);

            $sql = "INSERT INTO $database.$table VALUES ('column with enclosure \"\", and comma inside text',
 'second column enclosure in text \"\"')";
            $this->connection->query($sql);

            $sql = "INSERT INTO $database.$table VALUES ('column with backslash \ inside',
 'column with backslash and enclosure \\\"\"')";
            $this->connection->query($sql);
        } catch (\Throwable $exception) {
            print $exception->getMessage();
        }

        $specification = new DatadirTestSpecification(
            $testDirectory . '/source/data',
            0,
            'Extracted table into: "out.c-main.test-1".' . PHP_EOL,
            null,
            $testDirectory . '/expected/data/out'
        );
        $tempDatadir = $this->getTempDatadir($specification);

        $configuration['parameters']['db'] = $credentials;
        file_put_contents(
            $tempDatadir->getTmpFolder() . '/config.json',
            json_encode($configuration, JSON_PRETTY_PRINT)
        );
        $process = $this->runScript($tempDatadir->getTmpFolder());
        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }

    public function testExtractEmptyDataWithRestrictedCharacterInDatabaseName(): void
    {
        $testDirectory = __DIR__ . '/empty-data';

        $configuration = json_decode((string) file_get_contents($testDirectory . '/config.json'), true);
        $credentials = $this->getCredentials();
        $database = $credentials['database'];
        $table = 'test_1';

        $this->createDatabase($database);
        $this->createTable($database, $table);
        $this->insertBasicData($database, $table);

        $specification = new DatadirTestSpecification(
            $testDirectory . '/source/data',
            1,
            null,
            'Object "database"_name" contain restricted character \'"\'.' . PHP_EOL,
            $testDirectory . '/expected/data/out'
        );
        $tempDatadir = $this->getTempDatadir($specification);

        $configuration['parameters']['db'] = $credentials;
        $configuration['parameters']['table']['schema'] = 'database"_name';
        file_put_contents(
            $tempDatadir->getTmpFolder() . '/config.json',
            json_encode($configuration, JSON_PRETTY_PRINT)
        );
        $process = $this->runScript($tempDatadir->getTmpFolder());
        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }

    public function testExtractEmptyDataWithRestrictedCharacterInTableName(): void
    {
        $testDirectory = __DIR__ . '/empty-data';

        $configuration = json_decode((string) file_get_contents($testDirectory . '/config.json'), true);
        $credentials = $this->getCredentials();
        $database = $credentials['database'];
        $table = 'test_1';

        $this->createDatabase($database);
        $this->createTable($database, $table);
        $this->insertBasicData($database, $table);

        $specification = new DatadirTestSpecification(
            $testDirectory . '/source/data',
            1,
            null,
            'Object "te"st_1" contain restricted character \'"\'.' . PHP_EOL,
            $testDirectory . '/expected/data/out'
        );
        $tempDatadir = $this->getTempDatadir($specification);

        $configuration['parameters']['db'] = $credentials;
        $configuration['parameters']['table']['tableName'] = 'te"st_1';
        file_put_contents(
            $tempDatadir->getTmpFolder() . '/config.json',
            json_encode($configuration, JSON_PRETTY_PRINT)
        );
        $process = $this->runScript($tempDatadir->getTmpFolder());
        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }

    public function testExtractEmptyDataWithRestrictedCharacterInColumnName(): void
    {
        $testDirectory = __DIR__ . '/empty-data';

        $configuration = json_decode((string) file_get_contents($testDirectory . '/config.json'), true);
        $credentials = $this->getCredentials();
        $database = $credentials['database'];
        $table = 'test_1';

        $this->createDatabase($database);
        $this->createTable($database, $table);
        $this->insertBasicData($database, $table);

        $specification = new DatadirTestSpecification(
            $testDirectory . '/source/data',
            1,
            null,
            'Object "col"umn1" contain restricted character \'"\'.' . PHP_EOL,
            $testDirectory . '/expected/data/out'
        );
        $tempDatadir = $this->getTempDatadir($specification);

        $configuration['parameters']['db'] = $credentials;
        $configuration['parameters']['table']['tableName'] = 'test_1';
        $configuration['parameters']['columns'] = ['col"umn1'];
        file_put_contents(
            $tempDatadir->getTmpFolder() . '/config.json',
            json_encode($configuration, JSON_PRETTY_PRINT)
        );
        $process = $this->runScript($tempDatadir->getTmpFolder());
        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }

    public function testExtractColumn1FromBasicData(): void
    {
        $testDirectory = __DIR__ . '/basic-data-export-one-column';

        $configuration = json_decode((string) file_get_contents($testDirectory . '/config.json'), true);
        $credentials = $this->getCredentials();
        $database = $credentials['database'];
        $table = 'test_1';

        $this->createDatabase($database);
        $this->createTable($database, $table);
        $this->insertBasicData($database, $table);

        $specification = new DatadirTestSpecification(
            $testDirectory . '/source/data',
            0,
            'Extracted table into: "out.c-main.test-1".' . PHP_EOL,
            null,
            $testDirectory . '/expected/data/out'
        );
        $tempDatadir = $this->getTempDatadir($specification);

        $configuration['parameters']['db'] = $credentials;
        file_put_contents(
            $tempDatadir->getTmpFolder() . '/config.json',
            json_encode($configuration, JSON_PRETTY_PRINT)
        );
        $process = $this->runScript($tempDatadir->getTmpFolder());
        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }

    public function testExtractWithUserSql(): void
    {
        $testDirectory = __DIR__ . '/aggregated-data';

        $configuration = json_decode((string) file_get_contents($testDirectory . '/config.json'), true);
        $credentials = $this->getCredentials();
        $database = $credentials['database'];
        $table = 'test_2';

        $this->createDatabase($database);
        $this->createTable($database, $table);
        $this->insertAggregatedBasicData($database, $table);

        $specification = new DatadirTestSpecification(
            $testDirectory . '/source/data',
            0,
            'Extracted table into: "out.c-main.test-2".' . PHP_EOL,
            null,
            $testDirectory . '/expected/data/out'
        );
        $tempDatadir = $this->getTempDatadir($specification);

        $configuration['parameters']['db'] = $credentials;
        file_put_contents(
            $tempDatadir->getTmpFolder() . '/config.json',
            json_encode($configuration, JSON_PRETTY_PRINT)
        );
        $process = $this->runScript($tempDatadir->getTmpFolder());
        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }

    public function testExtractFromNonExistingDatabase(): void
    {
        $testDirectory = __DIR__ . '/empty-data';

        $configuration = json_decode((string) file_get_contents($testDirectory . '/config.json'), true);
        $credentials = $this->getCredentials();
        $database = $credentials['database'];
        $table = 'test_1';

        $this->createDatabase($database);
        $this->createTable($database, $table);
        $this->insertBasicData($database, $table);

        $specification = new DatadirTestSpecification(
            $testDirectory . '/source/data',
            1,
            null,
            'Database "invalid_database" does not exist.' . PHP_EOL,
            $testDirectory . '/expected/data/out'
        );
        $tempDatadir = $this->getTempDatadir($specification);

        $configuration['parameters']['db'] = $credentials;
        $configuration['parameters']['table']['schema'] = 'invalid_database';
        file_put_contents(
            $tempDatadir->getTmpFolder() . '/config.json',
            json_encode($configuration, JSON_PRETTY_PRINT)
        );
        $process = $this->runScript($tempDatadir->getTmpFolder());

        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }

    public function testExtractFromNonExistingTable(): void
    {
        $testDirectory = __DIR__ . '/empty-data';

        $configuration = json_decode((string) file_get_contents($testDirectory . '/config.json'), true);
        $credentials = $this->getCredentials();
        $database = $credentials['database'];
        $table = 'test_1';

        $this->createDatabase($database);
        $this->createTable($database, $table);
        $this->insertBasicData($database, $table);

        $specification = new DatadirTestSpecification(
            $testDirectory . '/source/data',
            1,
            null,
            'Table "invalid_table" does not exist in database "ex_teradata_test".' . PHP_EOL,
            $testDirectory . '/expected/data/out'
        );
        $tempDatadir = $this->getTempDatadir($specification);

        $configuration['parameters']['db'] = $credentials;
        $configuration['parameters']['table'] = [
            'schema' => 'ex_teradata_test',
            'tableName' => 'invalid_table',
        ];

        file_put_contents(
            $tempDatadir->getTmpFolder() . '/config.json',
            json_encode($configuration, JSON_PRETTY_PRINT)
        );
        $process = $this->runScript($tempDatadir->getTmpFolder());

        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }

    public function testExtractEmptyTable(): void
    {
        $testDirectory = __DIR__ . '/empty-table';

        $configuration = json_decode((string) file_get_contents($testDirectory . '/config.json'), true);
        $credentials = $this->getCredentials();
        $database = $credentials['database'];
        $table = 'test_1';

        $this->createDatabase($database);
        $this->createTable($database, $table);

        $specification = new DatadirTestSpecification(
            $testDirectory . '/source/data',
            0,
            'Extracted table into: "out.c-main.test-1".' . PHP_EOL,
            null,
            $testDirectory . '/expected/data/out'
        );
        $tempDatadir = $this->getTempDatadir($specification);

        $configuration['parameters']['db'] = $credentials;

        file_put_contents(
            $tempDatadir->getTmpFolder() . '/config.json',
            json_encode($configuration, JSON_PRETTY_PRINT)
        );
        $process = $this->runScript($tempDatadir->getTmpFolder());

        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }

    public function testExtractTableWithByteColumn(): void
    {
        $testDirectory = __DIR__ . '/basic-data-byte-column';

        $configuration = json_decode((string) file_get_contents($testDirectory . '/config.json'), true);
        $credentials = $this->getCredentials();

        $specification = new DatadirTestSpecification(
            $testDirectory . '/source/data',
            1,
            null,
            'You are probably trying to export one or more columns with data type "byte"'
            . ' which is not allowed.' . PHP_EOL,
            $testDirectory . '/expected/data/out'
        );
        $tempDatadir = $this->getTempDatadir($specification);

        $credentials['database'] = 'DBC';
        $configuration['parameters']['db'] = $credentials;

        file_put_contents(
            $tempDatadir->getTmpFolder() . '/config.json',
            json_encode($configuration, JSON_PRETTY_PRINT)
        );
        $process = $this->runScript($tempDatadir->getTmpFolder());

        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }
}
