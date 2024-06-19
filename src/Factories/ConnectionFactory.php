<?php

declare(strict_types=1);

namespace Keboola\ExTeradata\Factories;

use Dibi\Connection;
use Keboola\ExTeradata\ExceptionHandler;
use Throwable;

class ConnectionFactory
{
    public function create(string $host, int $port, string $user, string $password): Connection
    {
        try {
            return new Connection([
                'dsn' => sprintf('DRIVER={Teradata};DBCName=%s;TDMSTPortNumber=%s;Charset=UTF8', $host, $port),
                'driver'   => 'odbc',
                'username' => $user,
                'password' => $password,
            ]);
        } catch (Throwable $exception) {
            throw (new ExceptionHandler())->createException($exception);
        }
    }
}
