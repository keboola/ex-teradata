<?php

declare(strict_types=1);

namespace Keboola\ExTeradata\Tests\Functional;

use Dibi\Connection;
use Keboola\DatadirTests\AbstractDatadirTestCase;
use Keboola\DatadirTests\DatadirTestSpecification;

class DatadirTest extends AbstractDatadirTestCase
{
    private function createConnection(string $host, string $user, string $password): Connection
    {
        return new Connection([
            'dsn' => sprintf('DRIVER={Teradata};DBCName=%s', $host),
            'driver'   => 'odbc',
            'username' => $user,
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
            'user' => getenv('TERADATA_USERNAME'),
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
            $credentials['user'],
            $credentials['#password']
        );

        $database = 'ex_teradata_test';

        $connection->query('DELETE DATABASE ' . $database);
        $connection->query('DROP DATABASE ' . $database);
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
            $credentials['user'],
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

    public function testInvalidUser(): void
    {
        $testDirectory = __DIR__ . '/empty-data';

        $configuration = json_decode((string) file_get_contents($testDirectory . '/config.json'), true);
        $credentials = $this->getCredentials();

        $connection = $this->createConnection(
            $credentials['host'],
            $credentials['user'],
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

        $connection = $this->createConnection(
            $credentials['host'],
            $credentials['user'],
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

        $connection = $this->createConnection(
            $credentials['host'],
            $credentials['user'],
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

    public function testWithoutSpecifiedTable(): void
    {
        $testDirectory = __DIR__ . '/empty-data';

        $configuration = json_decode((string) file_get_contents($testDirectory . '/config.json'), true);
        $credentials = $this->getCredentials();

        $connection = $this->createConnection(
            $credentials['host'],
            $credentials['user'],
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

        $connection = $this->createConnection(
            $credentials['host'],
            $credentials['user'],
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

        $connection = $this->createConnection(
            $credentials['host'],
            $credentials['user'],
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
            'Object \'database"_name\' contain restricted character \'"\'.' . PHP_EOL,
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

        $connection = $this->createConnection(
            $credentials['host'],
            $credentials['user'],
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
            'Object \'te"st_1\' contain restricted character \'"\'.' . PHP_EOL,
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

        $connection = $this->createConnection(
            $credentials['host'],
            $credentials['user'],
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
            'Object \'col"umn1\' contain restricted character \'"\'.' . PHP_EOL,
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

        $connection = $this->createConnection(
            $credentials['host'],
            $credentials['user'],
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

        $connection = $this->createConnection(
            $credentials['host'],
            $credentials['user'],
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

        $connection = $this->createConnection(
            $credentials['host'],
            $credentials['user'],
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

        $connection = $this->createConnection(
            $credentials['host'],
            $credentials['user'],
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
            $credentials['user'],
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
