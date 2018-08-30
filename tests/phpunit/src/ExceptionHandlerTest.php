<?php

declare(strict_types=1);

namespace Keboola\ExTeradata\Tests\Unit;

use Dibi\DriverException;
use Keboola\Component\UserException;
use Keboola\ExTeradata\ExceptionHandler;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class ExceptionHandlerTest extends MockeryTestCase
{
    /** @var ExceptionHandler */
    private $exceptionHandler;

    public function setUp(): void
    {
        parent::setUp();

        $this->exceptionHandler = new ExceptionHandler();
    }

    public function testExceptionHandlerInvalidHostThrowUserException(): void
    {
        $exception = new DriverException(
            '[unixODBC][Teradata][WSock32 DLL] (439) WSA E HostUnreach:'
            . ' The Teradata server can\'t currently be reached over this network 08001'
        );

        $this->expectException(UserException::class);
        $this->expectExceptionMessage('The Teradata server can\'t currently be reached over this network.');
        $this->exceptionHandler->handleException($exception);
    }

    public function testExceptionHandlerInvalidCredentialsThrowUserException(): void
    {
        $exception = new DriverException(
            '[unixODBC][Teradata][ODBC Teradata Driver][Teradata Database] (210)'
            . ' The UserId, Password or Account is invalid. FailCode = -8017 28000'
        );

        $this->expectException(UserException::class);
        $this->expectExceptionMessage('The Username or Password is invalid.');
        $this->exceptionHandler->handleException($exception);
    }

    public function testExceptionHandlerNonExistingDatabaseThrowUserException(): void
    {
        $exception = new DriverException(
            '[Teradata][ODBC Teradata Driver][Teradata Database](-3802)Database'
            . ' \'invalid_database_name\' does not exist. S0002'
        );

        $this->expectException(UserException::class);
        $this->expectExceptionMessage('Database \'invalid_database_name\' does not exist.');
        $this->exceptionHandler->handleException($exception);
    }

    public function testExceptionHandlerNotExistingTableThrowUserException(): void
    {
        $exception = new DriverException(
            '[Teradata][ODBC Teradata Driver][Teradata Database](-3807)Object'
            . ' \'database_name.invalid_table_name\' does not exist. S0002'
        );

        $this->expectException(UserException::class);
        $this->expectExceptionMessage('Table \'invalid_table_name\' does not exist in database \'database_name\'.');
        $this->exceptionHandler->handleException($exception);
    }

    public function testExceptionHandlerRuntimeExceptionIsPassedAbove(): void
    {
        $exception = new \RuntimeException('Some exception');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Some exception');
        $this->exceptionHandler->handleException($exception);
    }
}
