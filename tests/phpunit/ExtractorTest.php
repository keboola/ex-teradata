<?php

declare(strict_types=1);

namespace Keboola\ExTeradata\Tests\Unit;

use DG\BypassFinals;
use Dibi\Connection;
use Dibi\DriverException;
use Dibi\Result;
use Dibi\Row;
use Keboola\Component\Logger;
use Keboola\Component\UserException;
use Keboola\Csv\CsvWriter;
use Keboola\ExTeradata\ExceptionHandler;
use Keboola\ExTeradata\Extractor;
use Keboola\ExTeradata\Factories\CsvWriterFactory;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class ExtractorTest extends MockeryTestCase
{
    /** @var Connection|MockInterface */
    private $connectionMock;

    /** @var CsvWriterFactory|MockInterface */
    private $csvWriterFactoryMock;

    /** @var Extractor */
    private $extractor;

    public function setUp(): void
    {
        parent::setUp();

        BypassFinals::enable();
        $this->connectionMock = \Mockery::mock(Connection::class);
        $this->csvWriterFactoryMock = \Mockery::mock(CsvWriterFactory::class);
        $this->extractor = $extractor = new Extractor(
            $this->connectionMock,
            $this->csvWriterFactoryMock,
            new ExceptionHandler(),
            new Logger()
        );
    }

    public function testExtractTableFromNonExistingDatabaseThrowsUserException(): void
    {
        $this->connectionMock->shouldReceive('nativeQuery')
            ->once()
            ->with("SELECT * FROM database_name.table")
            ->andThrow(
                DriverException::class,
                '[Teradata][ODBC Teradata Driver][Teradata Database](-3802)Database'
                . ' \'database_name\' does not exist. S0002'
            );

        $this->expectException(UserException::class);
        $this->expectExceptionMessage('Database "database_name" does not exist.');

        $this->extractor->extractTable(
            'SELECT * FROM database_name.table',
            'table.csv'
        );
    }

    public function testExtractTableFromNonExistingTableThrowsUserException(): void
    {
        $this->connectionMock->shouldReceive('nativeQuery')
            ->once()
            ->with("SELECT * FROM database_name.table")
            ->andThrow(
                DriverException::class,
                '[Teradata][ODBC Teradata Driver][Teradata Database](-3807)Object'
                . ' \'database_name.table\' does not exist. S0002'
            );

        $this->expectException(UserException::class);
        $this->expectExceptionMessage('Table "table" does not exist in database "database_name".');

        $this->extractor->extractTable(
            'SELECT * FROM database_name.table',
            'table.csv'
        );
    }

    public function testExtractTableFetchFailsOnUnhandledExceptionThrowRuntimeException(): void
    {
        $csvWriterMock = \Mockery::mock(CsvWriter::class);
        $csvWriterMock->shouldReceive('writeRow')
            ->once()
            ->with(['column1', 'column2'])
            ->andReturnNull();

        $this->csvWriterFactoryMock->shouldReceive('create')
            ->once()
            ->withAnyArgs()
            ->andReturn($csvWriterMock);

        $getInfoResultMock = \Mockery::mock(\Dibi\Reflection\Result::class);
        $getInfoResultMock->shouldReceive('getColumnNames')
            ->once()
            ->withNoArgs()
            ->andReturn(['column1', 'column2']);

        $resultMock = \Mockery::mock(Result::class);
        $resultMock->shouldReceive('fetch')
            ->once()
            ->withNoArgs()
            ->andThrow(
                \InvalidArgumentException::class,
                'Invalid argument message.'
            );
        $resultMock->shouldReceive('getInfo')
            ->once()
            ->withNoArgs()
            ->andReturn($getInfoResultMock);

        $this->connectionMock->shouldReceive('nativeQuery')
            ->once()
            ->withAnyArgs()
            ->andReturn($resultMock);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid argument message.');

        $this->extractor->extractTable(
            'SELECT * FROM database_name.table',
            'table.csv'
        );
    }

    public function testExtractTableWithEmptyResultSuccessfully(): void
    {
        $getInfoResultMock = \Mockery::mock(\Dibi\Reflection\Result::class);
        $getInfoResultMock->shouldReceive('getColumnNames')
            ->once()
            ->withNoArgs()
            ->andReturn(['column1', 'column2']);

        $resultMock = \Mockery::mock(Result::class);
        $resultMock->shouldReceive('fetch')
            ->once()
            ->withNoArgs()
            ->andReturn([]);
        $resultMock->shouldReceive('getInfo')
            ->once()
            ->withNoArgs()
            ->andReturn($getInfoResultMock);

        $csvWriterMock = \Mockery::mock(CsvWriter::class);
        $csvWriterMock->shouldReceive('writeRow')
            ->once()
            ->with(['column1', 'column2'])
            ->andReturnNull();

        $this->csvWriterFactoryMock->shouldReceive('create')
            ->once()
            ->withAnyArgs()
            ->andReturn($csvWriterMock);

        $this->connectionMock->shouldReceive('nativeQuery')
            ->once()
            ->with("SELECT * FROM database_name.table")
            ->andReturn($resultMock);

        $this->extractor->extractTable(
            'SELECT * FROM database_name.table',
            'table.csv'
        );
    }

    public function testExtractTableSuccessfully(): void
    {
        $rows = [
            new Row([
                'column1' => 'row1',
                'column2' => 1,
            ]),
            new Row([
                'column1' => 'row2',
                'column2' => 2,
            ]),
        ];

        $getInfoResultMock = \Mockery::mock(\Dibi\Reflection\Result::class);
        $getInfoResultMock->shouldReceive('getColumnNames')
            ->once()
            ->withNoArgs()
            ->andReturn(['column1', 'column2']);

        $resultMock = \Mockery::mock(Result::class);
        $resultMock->shouldReceive('fetch')
            ->times(3)
            ->withNoArgs()
            ->andReturnUsing(function () use (&$rows) {
                $row = current($rows);
                next($rows);
                return $row;
            });
        $resultMock->shouldReceive('getInfo')
            ->once()
            ->withNoArgs()
            ->andReturn($getInfoResultMock);

        $csvWriterMock = \Mockery::spy(CsvWriter::class);
        $csvWriterMock->shouldReceive('writeRow')
            ->times(3)
            ->withAnyArgs()
            ->andReturnNull();
        $this->csvWriterFactoryMock->shouldReceive('create')
            ->once()
            ->withAnyArgs()
            ->andReturn($csvWriterMock);

        $this->connectionMock->shouldReceive('nativeQuery')
            ->once()
            ->with("SELECT * FROM database_name.table")
            ->andReturn($resultMock);

        $this->extractor->extractTable(
            'SELECT * FROM database_name.table',
            'table.csv'
        );
    }
}
