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

    private function insertBasicData(Connection $connection, string $database): void
    {
        $table = 'test_1';

        try {
            $sql = sprintf('CREATE DATABASE %s AS PERMANENT=1e9', $database);
            $connection->query($sql);

            $sql = "CREATE TABLE $database.$table (column1 VARCHAR (16), column2 INTEGER)";
            $connection->query($sql);

            $sql = "INSERT INTO $database.$table  VALUES ('row1', 1)";
            $connection->query($sql);

            $sql = "INSERT INTO $database.$table  VALUES ('row2', 2)";
            $connection->query($sql);
        } catch (\Throwable $exception) {
            print $exception->getMessage();
        }
    }

    public function testBasicData(): void
    {
        $testDirectory = __DIR__ . '/basic-data';

        $configuration = json_decode((string) file_get_contents($testDirectory . '/config.json'), true);
        $credentials = $this->getCredentials();

        $connection = $this->createConnection(
            $credentials['host'],
            $credentials['username'],
            $credentials['#password']
        );
        $this->insertBasicData($connection, $credentials['database']);

        $specification = new DatadirTestSpecification(
            $testDirectory . '/source/data',
            0,
            'Extracted tables: "test_1".' . PHP_EOL,
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

    public function testInvalidCredentials(): void
    {
        $testDirectory = __DIR__ . '/invalid-credentials';

        $configuration = json_decode((string) file_get_contents($testDirectory . '/config.json'), true);
        $credentials = $this->getCredentials();

        $connection = $this->createConnection(
            $credentials['host'],
            $credentials['username'],
            $credentials['#password']
        );
        $this->insertBasicData($connection, $credentials['database']);

        $specification = new DatadirTestSpecification(
            $testDirectory . '/source/data',
            1,
            null,
            'The Teradata server can\'t currently be reached over this network.' . PHP_EOL,
            $testDirectory . '/expected/data/out'
        );
        $tempDatadir = $this->getTempDatadir($specification);

        $credentials['host'] = 'invalid_username';
        $configuration['parameters']['db'] = $credentials;
        file_put_contents(
            $tempDatadir->getTmpFolder() . '/config.json',
            json_encode($configuration, JSON_PRETTY_PRINT)
        );
        $process = $this->runScript($tempDatadir->getTmpFolder());
        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }

    // test without credentials in config
    public function testWithoutCredentials(): void
    {
        $testDirectory = __DIR__ . '/invalid-credentials';

        $configuration = json_decode((string) file_get_contents($testDirectory . '/config.json'), true);
        $credentials = $this->getCredentials();

        $connection = $this->createConnection(
            $credentials['host'],
            $credentials['username'],
            $credentials['#password']
        );
        $this->insertBasicData($connection, $credentials['database']);

        $specification = new DatadirTestSpecification(
            $testDirectory . '/source/data',
            1,
            null,
            'The child node "db" at path "root.parameters" must be configured.' . PHP_EOL,
            $testDirectory . '/expected/data/out'
        );
        $tempDatadir = $this->getTempDatadir($specification);

        // $configuration['parameters']['db'] = $credentials;
        file_put_contents(
            $tempDatadir->getTmpFolder() . '/config.json',
            json_encode($configuration, JSON_PRETTY_PRINT)
        );
        $process = $this->runScript($tempDatadir->getTmpFolder());
        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }

    // test without defined tables

    // test with wrong defined table

    // test incremental table
}
