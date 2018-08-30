<?php

declare(strict_types=1);

namespace Keboola\ExTeradata\Tests\Functional;

use Dibi\Connection;
use Keboola\DatadirTests\AbstractDatadirTestCase;
use Keboola\DatadirTests\DatadirTestSpecification;

class DatadirTest extends AbstractDatadirTestCase
{
    private function createConnection(string $host, string $usename, string $password): Connection
    {
        return new Connection([
            'dsn' => sprintf('DRIVER={Teradata};DBCName=%s', $host),
            'driver'   => 'odbc',
            'username' => $usename,
            'password' => $password,
        ]);
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
            'username' => getenv('TERADATA_USERNAME'),
            '#password' => getenv('TERADATA_PASSWORD'),
            'database' => getenv('TERADATA_DATABASE'),
        ];
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $credentials = $this->getCredentials();
        $connection = $this->createConnection(
            $credentials['host'],
            $credentials['username'],
            $credentials['#password']
        );

        $database = 'ex_teradata_test';
        try {
            $connection->query('DELETE DATABASE ' . $database);
            $connection->query('DROP DATABASE ' . $database);
        } catch (\Throwable $exception) {
            print $exception->getMessage();
        }
    }

    private function createDatabase(Connection $connection, string $database): void
    {
        try {
            $sql = sprintf('CREATE DATABASE %s AS PERMANENT=1e9', $database);
            $connection->query($sql);
        } catch (\Throwable $exception) {
            print $exception->getMessage();
        }
    }

    private function createTable(Connection $connection, string $database, string $table): void
    {
        try {
            $sql = "CREATE TABLE $database.$table (column1 VARCHAR (16), column2 INTEGER)";
            $connection->query($sql);
        } catch (\Throwable $exception) {
            print $exception->getMessage();
        }
    }

    private function insertBasicData(Connection $connection, string $database, string $table): void
    {
        try {
            $sql = "INSERT INTO $database.$table  VALUES ('row1', 1)";
            $connection->query($sql);

            $sql = "INSERT INTO $database.$table  VALUES ('row2', 2)";
            $connection->query($sql);
        } catch (\Throwable $exception) {
            print $exception->getMessage();
        }
    }

    private function insertAggregatedBasicData(Connection $connection, string $database, string $table): void
    {
        try {
            $sql = "INSERT INTO $database.$table  VALUES ('row1', 1)";
            $connection->query($sql);

            $sql = "INSERT INTO $database.$table  VALUES ('row2', 2)";
            $connection->query($sql);

            $sql = "INSERT INTO $database.$table  VALUES ('row3', 1)";
            $connection->query($sql);

            $sql = "INSERT INTO $database.$table  VALUES ('row4', 1)";
            $connection->query($sql);
        } catch (\Throwable $exception) {
            print $exception->getMessage();
        }
    }

    public function testInvalidHostname(): void
    {
        $testDirectory = __DIR__ . '/empty-data';

        $configuration = json_decode((string) file_get_contents($testDirectory . '/config.json'), true);
        $credentials = $this->getCredentials();

        $connection = $this->createConnection(
            $credentials['host'],
            $credentials['username'],
            $credentials['#password']
        );
        $database = $credentials['database'];
        $table = 'test_1';

        $this->createDatabase($connection, $database);
        $this->createTable($connection, $database, $table);
        $this->insertBasicData($connection, $database, $table);

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

    public function testInvalidUsername(): void
    {
        $testDirectory = __DIR__ . '/empty-data';

        $configuration = json_decode((string) file_get_contents($testDirectory . '/config.json'), true);
        $credentials = $this->getCredentials();

        $connection = $this->createConnection(
            $credentials['host'],
            $credentials['username'],
            $credentials['#password']
        );
        $database = $credentials['database'];
        $table = 'test_1';

        $this->createDatabase($connection, $database);
        $this->createTable($connection, $database, $table);
        $this->insertBasicData($connection, $database, $table);

        $specification = new DatadirTestSpecification(
            $testDirectory . '/source/data',
            1,
            null,
            'The Username or Password is invalid.' . PHP_EOL,
            $testDirectory . '/expected/data/out'
        );
        $tempDatadir = $this->getTempDatadir($specification);

        $credentials['username'] = 'invalid_username';
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

        $connection = $this->createConnection(
            $credentials['host'],
            $credentials['username'],
            $credentials['#password']
        );
        $database = $credentials['database'];
        $table = 'test_1';

        $this->createDatabase($connection, $database);
        $this->createTable($connection, $database, $table);
        $this->insertBasicData($connection, $database, $table);

        $specification = new DatadirTestSpecification(
            $testDirectory . '/source/data',
            1,
            null,
            'The Username or Password is invalid.' . PHP_EOL,
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

        $connection = $this->createConnection(
            $credentials['host'],
            $credentials['username'],
            $credentials['#password']
        );
        $database = $credentials['database'];
        $table = 'test_1';

        $this->createDatabase($connection, $database);
        $this->createTable($connection, $database, $table);
        $this->insertBasicData($connection, $database, $table);

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

    public function testWithoutSpecifiedTables(): void
    {
        $testDirectory = __DIR__ . '/empty-data';

        $configuration = json_decode((string) file_get_contents($testDirectory . '/config.json'), true);
        $credentials = $this->getCredentials();

        $connection = $this->createConnection(
            $credentials['host'],
            $credentials['username'],
            $credentials['#password']
        );
        $database = $credentials['database'];
        $table = 'test_1';

        $this->createDatabase($connection, $database);
        $this->createTable($connection, $database, $table);
        $this->insertBasicData($connection, $database, $table);

        $specification = new DatadirTestSpecification(
            $testDirectory . '/source/data',
            1,
            null,
            'Table name must be set in configuration.' . PHP_EOL,
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

        $connection = $this->createConnection(
            $credentials['host'],
            $credentials['username'],
            $credentials['#password']
        );
        $database = $credentials['database'];
        $table = 'test_1';

        $this->createDatabase($connection, $database);
        $this->createTable($connection, $database, $table);
        $this->insertBasicData($connection, $database, $table);

        $specification = new DatadirTestSpecification(
            $testDirectory . '/source/data',
            0,
            'Extracted table: "test_1".' . PHP_EOL,
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

    public function testExtractColumn1FromBasicData(): void
    {
        $testDirectory = __DIR__ . '/basic-data-export-one-column';

        $configuration = json_decode((string) file_get_contents($testDirectory . '/config.json'), true);
        $credentials = $this->getCredentials();

        $connection = $this->createConnection(
            $credentials['host'],
            $credentials['username'],
            $credentials['#password']
        );
        $database = $credentials['database'];
        $table = 'test_1';

        $this->createDatabase($connection, $database);
        $this->createTable($connection, $database, $table);
        $this->insertBasicData($connection, $database, $table);

        $specification = new DatadirTestSpecification(
            $testDirectory . '/source/data',
            0,
            'Extracted table: "test_1".' . PHP_EOL,
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

        $connection = $this->createConnection(
            $credentials['host'],
            $credentials['username'],
            $credentials['#password']
        );

        $database = $credentials['database'];
        $table = 'test_2';

        $this->createDatabase($connection, $database);
        $this->createTable($connection, $database, $table);
        $this->insertAggregatedBasicData($connection, $database, $table);

        $specification = new DatadirTestSpecification(
            $testDirectory . '/source/data',
            0,
            'Extracted table: "test_2".' . PHP_EOL,
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

        $connection = $this->createConnection(
            $credentials['host'],
            $credentials['username'],
            $credentials['#password']
        );
        $database = $credentials['database'];
        $table = 'test_1';

        $this->createDatabase($connection, $database);
        $this->createTable($connection, $database, $table);
        $this->insertBasicData($connection, $database, $table);

        $specification = new DatadirTestSpecification(
            $testDirectory . '/source/data',
            1,
            null,
            'Database \'invalid_database\' does not exist.' . PHP_EOL,
            $testDirectory . '/expected/data/out'
        );
        $tempDatadir = $this->getTempDatadir($specification);

        $credentials['database'] = 'invalid_database';
        $configuration['parameters']['db'] = $credentials;
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

        $connection = $this->createConnection(
            $credentials['host'],
            $credentials['username'],
            $credentials['#password']
        );
        $database = $credentials['database'];
        $table = 'test_1';

        $this->createDatabase($connection, $database);
        $this->createTable($connection, $database, $table);
        $this->insertBasicData($connection, $database, $table);

        $specification = new DatadirTestSpecification(
            $testDirectory . '/source/data',
            1,
            null,
            'Table \'invalid_table\' does not exist in database \'ex_teradata_test\'.' . PHP_EOL,
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
        $testDirectory = __DIR__ . '/empty-data';

        $configuration = json_decode((string) file_get_contents($testDirectory . '/config.json'), true);
        $credentials = $this->getCredentials();

        $connection = $this->createConnection(
            $credentials['host'],
            $credentials['username'],
            $credentials['#password']
        );
        $database = $credentials['database'];
        $table = 'test_1';

        $this->createDatabase($connection, $database);
        $this->createTable($connection, $database, $table);

        $specification = new DatadirTestSpecification(
            $testDirectory . '/source/data',
            1,
            null,
            'Table \'invalid_table\' does not exist in database \'ex_teradata_test\'.' . PHP_EOL,
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
}
