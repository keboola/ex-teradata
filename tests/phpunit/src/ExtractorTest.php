<?php

declare(strict_types=1);

namespace Keboola\ExTeradata\Tests\Unit\src;

use DG\BypassFinals;
use Dibi\Connection;
use Dibi\DriverException;
use Dibi\Result;
use Dibi\Row;
use Keboola\Component\UserException;
use Keboola\ExTeradata\Extractor;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class ExtractorTest extends MockeryTestCase
{
    /** @var Connection|MockInterface */
    private $connectionMock;

    public function setUp(): void
    {
        parent::setUp();

        BypassFinals::enable();
        $this->connectionMock = \Mockery::mock(Connection::class);
    }

    public function testTableExportSuccessful(): void
    {
        /** @var Result|MockInterface $resultMock */
        $resultMock = \Mockery::mock(Result::class);
        $resultMock->shouldReceive('fetchAll')
            ->once()
            ->withNoArgs()
            ->andReturn(
                [
                    new Row([
                        'column1' => 'row1',
                        'column2' => 1,
                    ]),
                    new Row([
                        'column1' => 'row2',
                        'column2' => 2,
                    ]),
                ]
            );

        $this->connectionMock->shouldReceive('query')
            ->once()
            ->with('SELECT * FROM database_name.table_name')
            ->andReturn($resultMock);

        $extractor = new Extractor($this->connectionMock, 'database_name');
        $data = $extractor->extractTable('table_name');

        self::assertCount(2, $data);

        $row1 = $data[0];
        $this->assertInstanceOf(Row::class, $row1);
        $this->assertEquals('row1', $row1['column1']);
        $this->assertEquals(1, $row1['column2']);
    }

    public function testTableExportOnEmptyTableThrowsUserException(): void
    {
        /** @var Result|MockInterface $resultMock */
        $resultMock = \Mockery::mock(Result::class);
        $resultMock->shouldReceive('fetchAll')
            ->once()
            ->withNoArgs()
            ->andReturn([]);

        $this->connectionMock->shouldReceive('query')
            ->once()
            ->with('SELECT * FROM database_name.table_name')
            ->andReturn($resultMock);

        $extractor = new Extractor($this->connectionMock, 'database_name');

        $this->expectException(UserException::class);
        $this->expectExceptionMessage('There are no rows in table \'database_name.table_name\'.');
        $extractor->extractTable('table_name');
    }

    public function testTableExportWithNonExistingDatabaseThrowsUserException(): void
    {
        $this->connectionMock->shouldReceive('query')
            ->once()
            ->with('SELECT * FROM invalid_database_name.table_name')
            ->andThrow(
                DriverException::class,
                'Teradata][ODBC Teradata Driver][Teradata Database](-3802)Database \'invalid_database_name\''
                 . ' does not exist. S0002'
            );

        $extractor = new Extractor($this->connectionMock, 'invalid_database_name');

        $this->expectException(UserException::class);
        $this->expectExceptionMessage('Database \'invalid_database_name\' does not exist.');
        $extractor->extractTable('table_name');
    }

    public function testTableExportWithNonExistingTableThrowsUserException(): void
    {
        $this->connectionMock->shouldReceive('query')
            ->once()
            ->with('SELECT * FROM database_name.invalid_table_name')
            ->andThrow(
                DriverException::class,
                '[Teradata][ODBC Teradata Driver][Teradata Database](-3807)Object \'database_name.invalid_table_name\''
                . ' does not exist. S0002'
            );

        $extractor = new Extractor($this->connectionMock, 'database_name');

        $this->expectException(UserException::class);
        $this->expectExceptionMessage('Table \'invalid_table_name\' does not exist in database \'database_name\'.');
        $extractor->extractTable('invalid_table_name');
    }

    public function testTableExportWithUnexpectedExceptionThrowsException(): void
    {
        $this->connectionMock->shouldReceive('query')
            ->once()
            ->with('SELECT * FROM database_name.table_name')
            ->andThrow(
                \RuntimeException::class,
                'Broken driver'
            );

        $extractor = new Extractor($this->connectionMock, 'database_name');

        $this->expectException(\Throwable::class);
        $this->expectExceptionMessage('Broken driver');
        $extractor->extractTable('table_name');
    }
}
