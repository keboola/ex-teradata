<?php

declare(strict_types=1);

namespace Keboola\ExTeradata\Tests\Unit;

use Keboola\Component\UserException;
use Keboola\ExTeradata\ExtractorHelper;
use PHPUnit\Framework\TestCase;

class ExtractorHelperTest extends TestCase
{
    private ExtractorHelper $extractorHelper;

    public function setUp(): void
    {
        $this->extractorHelper = new ExtractorHelper();
    }

    public function testValidateObjectWithIncorectStringThrowsuserException(): void
    {
        $this->expectException(UserException::class);
        $this->expectExceptionMessage('Object "bad"object" contain restricted character \'"\'.');

        $this->extractorHelper->validateObject('bad"object');
    }

    public function testGetExportSqlWithTableDefined(): void
    {
        $this->assertEquals(
            'SELECT * FROM "database_name"."table"',
            $this->extractorHelper->getExportSql('database_name', 'table', []),
        );
    }

    public function testGetExportSqlWithColumnsDefined(): void
    {
        $this->assertEquals(
            'SELECT "column1","column2" FROM "database_name"."table"',
            $this->extractorHelper->getExportSql(
                'database_name',
                'table',
                [
                    'column1',
                    'column2',
                ],
            ),
        );
    }
}
