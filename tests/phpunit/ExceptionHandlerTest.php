<?php

declare(strict_types=1);

namespace Keboola\ExTeradata\Tests\Unit;

use Dibi\DriverException;
use ErrorException;
use Keboola\Component\UserException;
use Keboola\ExTeradata\ExceptionHandler;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ExceptionHandlerTest extends TestCase
{
    private ExceptionHandler $exceptionHandler;

    public function setUp(): void
    {
        parent::setUp();

        $this->exceptionHandler = new ExceptionHandler();
    }

    public function testExceptionHandlerUnreachableNetwork(): void
    {
        $exception = $this->exceptionHandler->createException(
            new DriverException(
                'unixODBC][Teradata][WSock32 DLL] (424) WSA E NetUnreach: Network is unreachable 08S01',
            ),
        );

        $this->assertInstanceOf(UserException::class, $exception);
        $this->assertEquals(
            'Network is unreachable.',
            $exception->getMessage(),
        );
    }

    public function testExceptionHandlerServerNotAcceptingConnections(): void
    {
        $exception = $this->exceptionHandler->createException(
            new DriverException(
                '[unixODBC][Teradata][WSock32 DLL] (435) WSA E ConnRefused:'
                . ' The Teradata server is not accepting connections 08004',
            ),
        );

        $this->assertInstanceOf(UserException::class, $exception);
        $this->assertEquals(
            'The Teradata server is not accepting connections.',
            $exception->getMessage(),
        );
    }

    public function testExceptionHandlerNoResponseFromServer(): void
    {
        $exception = $this->exceptionHandler->createException(
            new DriverException(
                '[unixODBC][Teradata][WSock32 DLL] (434) WSA E TimedOut: No response'
                . ' received when attempting to connect to the Teradata server S1000',
            ),
        );

        $this->assertInstanceOf(UserException::class, $exception);
        $this->assertEquals(
            'No response received when attempting to connect to the Teradata server.',
            $exception->getMessage(),
        );
    }

    public function testExceptionHandlerCannotAssignRequestAddress(): void
    {
        $exception = $this->exceptionHandler->createException(
            new DriverException(
                '[unixODBC][Teradata][WSock32 DLL] (422) WSA E AddrNotAvail: Can\'t assign requested address 08S01',
            ),
        );

        $this->assertInstanceOf(UserException::class, $exception);
        $this->assertEquals(
            'Cannot assign requested address.',
            $exception->getMessage(),
        );
    }

    public function testExceptionHandlerInvalidHostThrowUserException(): void
    {
        $exception = $this->exceptionHandler->createException(
            new DriverException(
                '[unixODBC][Teradata][WSock32 DLL] (439) WSA E HostUnreach:'
                . ' The Teradata server can\'t currently be reached over this network 08001',
            ),
        );

        $this->assertInstanceOf(UserException::class, $exception);
        $this->assertEquals(
            'The Teradata server can\'t currently be reached over this network.',
            $exception->getMessage(),
        );
    }

    public function testExceptionHandlerInvalidCredentialsThrowUserException(): void
    {
        $exception = $this->exceptionHandler->createException(
            new DriverException(
                '[unixODBC][Teradata][ODBC Teradata Driver][Teradata Database] (210)'
                . ' The UserId, Password or Account is invalid. FailCode = -8017 28000',
            ),
        );

        $this->assertInstanceOf(UserException::class, $exception);
        $this->assertEquals('The User or Password is invalid.', $exception->getMessage());
    }

    public function testExceptionHandlerNonExistingDatabaseThrowUserException(): void
    {
        $exception = $this->exceptionHandler->createException(
            new DriverException(
                '[Teradata][ODBC Teradata Driver][Teradata Database](-3802)Database'
                . ' \'invalid_database_name\' does not exist. S0002',
            ),
        );

        $this->assertInstanceOf(UserException::class, $exception);
        $this->assertEquals('Database "invalid_database_name" does not exist.', $exception->getMessage());
    }

    public function testExceptionHandlerNotExistingTableThrowUserException(): void
    {
        $exception = $this->exceptionHandler->createException(
            new DriverException(
                '[Teradata][ODBC Teradata Driver][Teradata Database](-3807)Object'
                . ' \'database_name.invalid_table_name\' does not exist. S0002',
            ),
        );

        $this->assertInstanceOf(UserException::class, $exception);
        $this->assertEquals(
            'Table "invalid_table_name" does not exist in database "database_name".',
            $exception->getMessage(),
        );
    }

    public function testExceptionHandlerWithoutSelectAccessToTable(): void
    {
        $exception = $this->exceptionHandler->createException(
            new DriverException(
                '[Teradata][ODBC Teradata Driver][Teradata Database](-3523)'
                . 'The user does not have SELECT access to DBC.TVM. 37000',
            ),
        );

        $this->assertInstanceOf(UserException::class, $exception);
        $this->assertEquals(
            'The user does not have "SELECT" access to "DBC.TVM".',
            $exception->getMessage(),
        );
    }

    public function testExceptionHandlerInvalidParameters(): void
    {
        $exception = $this->exceptionHandler->createException(
            new DriverException(
                '[Teradata][ODBC Teradata Driver]Teradata DatabaseFunction \'TO_DATE\' called with an ' .
                'invalid number or type of parameters S1000',
            ),
        );

        $this->assertInstanceOf(UserException::class, $exception);
        $this->assertEquals(
            'Teradata DatabaseFunction "TO_DATE" called with an invalid number or type of parameters.',
            $exception->getMessage(),
        );
    }

    public function testExceptionHandlerInternalError(): void
    {
        $exception = $this->exceptionHandler->createException(
            new DriverException('[Teradata][ODBC Teradata Driver] (6) Internal Error (Exception). S1000'),
        );

        $this->assertInstanceOf(UserException::class, $exception);
        $this->assertEquals(
            'Teradata Internal Error.',
            $exception->getMessage(),
        );
    }

    public function testExceptionHandlerLogonsAreOnlyEnabledForUser(): void
    {
        $exception = $this->exceptionHandler->createException(
            new DriverException('[Teradata][ODBC Teradata Driver][Teradata Database] (210) Logons are only ' .
                'enabled for user DBC. FailCode = -3055 S1000'),
        );

        $this->assertInstanceOf(UserException::class, $exception);
        $this->assertEquals(
            'Logons are only enabled for user DBC.',
            $exception->getMessage(),
        );
    }

    public function testExceptionHandlerExportingBytesThrowUserException(): void
    {
        $exception = $this->exceptionHandler->createException(
            new ErrorException('A non-numeric value encountered {}'),
        );

        $this->assertInstanceOf(UserException::class, $exception);
        $this->assertEquals(
            'You are probably trying to export one or more columns with data type "byte" which is not allowed.',
            $exception->getMessage(),
        );
    }

    public function testExceptionHandlerRuntimeExceptionIsPassedAbove(): void
    {
        $exception = $this->exceptionHandler->createException(new RuntimeException('Some exception'));

        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertEquals('Some exception', $exception->getMessage());
    }
}
