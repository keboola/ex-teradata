<?php

namespace Keboola\ExTeradata\Tests\Unit\src;

use DG\BypassFinals;
use Dibi\Connection;
use Dibi\DriverException;
use Dibi\Result;
use Dibi\Row;
use Keboola\Component\UserException;
use Keboola\Csv\CsvWriter;
use Keboola\ExTeradata\CsvWriterFactory;
use Keboola\ExTeradata\ExceptionHandler;
use Keboola\ExTeradata\Extractor;
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
            new ExceptionHandler()
        );
    }

    public function testGetExportSqlWithTableDefined(): void
    {
        $this->assertEquals(
            'SELECT * FROM "database_name"."table"',
            $this->extractor->getExportSql('database_name', 'table', [])
        );
    }

    public function testGetExportSqlWithColumnsDefined(): void
    {
        $this->assertEquals(
            'SELECT "column1","column2" FROM "database_name"."table"',
            $this->extractor->getExportSql(
                'database_name',
                'table',
                [
                    'column1',
                    'column2',
                ]
            )
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
        $this->expectExceptionMessage('Database \'database_name\' does not exist.');

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
        $this->expectExceptionMessage('Table \'table\' does not exist in database \'database_name\'.');

        $this->extractor->extractTable(
            'SELECT * FROM database_name.table',
            'table.csv'
        );
    }

    public function testExtractTableNativeQueryFailsOnUnhandledExceptionThrowRuntimeException(): void
    {
        $exceptionHandlerMock = \Mockery::mock(ExceptionHandler::class);
        $exceptionHandlerMock->shouldReceive('handleException')
            ->once()
            ->withAnyArgs()
            ->andReturnNull();

        $this->connectionMock->shouldReceive('nativeQuery')
            ->once()
            ->withAnyArgs()
            ->andThrow(\InvalidArgumentException::class);

        $extractor = new Extractor(
            $this->connectionMock,
            $this->csvWriterFactoryMock,
            $exceptionHandlerMock
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('');

        $extractor->extractTable(
            'SELECT * FROM database_name.table',
            'table.csv'
        );
    }

    public function testExtractTableFetchFailsOnUnhandledExceptionThrowRuntimeException(): void
    {
        $exceptionHandlerMock = \Mockery::mock(ExceptionHandler::class);
        $exceptionHandlerMock->shouldReceive('handleException')
            ->once()
            ->withAnyArgs()
            ->andReturnNull();

        $this->csvWriterFactoryMock->shouldReceive('create')
            ->once()
            ->withAnyArgs()
            ->andReturn(\Mockery::mock(CsvWriter::class));

        $resultMock = \Mockery::mock(Result::class);
        $resultMock->shouldReceive('fetch')
            ->once()
            ->withNoArgs()
            ->andThrow(\InvalidArgumentException::class);

        $this->connectionMock->shouldReceive('nativeQuery')
            ->once()
            ->withAnyArgs()
            ->andReturn($resultMock);

        $extractor = new Extractor(
            $this->connectionMock,
            $this->csvWriterFactoryMock,
            $exceptionHandlerMock
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('');

        $extractor->extractTable(
            'SELECT * FROM database_name.table',
            'table.csv'
        );
    }

    public function testExtractTableWithNoColumnsThrowsUserException(): void
    {
        $rowMock = \Mockery::mock(Row::class);
        $rowMock->shouldReceive('toArray')
            ->once()
            ->withNoArgs()
            ->andReturn([]);

        $resultMock = \Mockery::mock(Result::class);
        $resultMock->shouldReceive('fetch')
            ->once()
            ->withNoArgs()
            ->andReturn($rowMock);

        $csvWriterMock = \Mockery::mock(CsvWriter::class);
        $this->csvWriterFactoryMock->shouldReceive('create')
            ->once()
            ->withAnyArgs()
            ->andReturn($csvWriterMock);

        $this->connectionMock->shouldReceive('nativeQuery')
            ->once()
            ->with("SELECT * FROM database_name.table")
            ->andReturn($resultMock);

        $this->expectException(\Throwable::class);
        $this->expectExceptionMessage('Table has no columns.');

        $this->extractor->extractTable(
            'SELECT * FROM database_name.table',
            'table.csv'
        );
    }

    public function testExtractTableWithEmptyResultThrowsUserException(): void
    {
        $resultMock = \Mockery::mock(Result::class);
        $resultMock->shouldReceive('fetch')
            ->once()
            ->withNoArgs()
            ->andReturn([]);

        $csvWriterMock = \Mockery::mock(CsvWriter::class);
        $this->csvWriterFactoryMock->shouldReceive('create')
            ->once()
            ->withAnyArgs()
            ->andReturn($csvWriterMock);

        $this->connectionMock->shouldReceive('nativeQuery')
            ->once()
            ->with("SELECT * FROM database_name.table")
            ->andReturn($resultMock);

        $this->expectException(\Throwable::class);
        $this->expectExceptionMessage('Empty export');

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
            ])
        ];
        $resultMock = \Mockery::mock(Result::class);
        $resultMock->shouldReceive('fetch')
            ->times(3)
            ->withNoArgs()
            ->andReturnUsing(function() use (&$rows) {
                $row = current($rows);
                next($rows);
                return $row;
            });

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
