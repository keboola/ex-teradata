<?php

declare(strict_types=1);

namespace Keboola\ExTeradata\Factories;

use Dibi\Connection;

class ConnectionFactory
{
    public function create(string $host, int $port, string $user, string $password): Connection
    {
        //TDMSTPortNumber
        return new Connection([
            'dsn' => sprintf('DRIVER={Teradata};DBCName=%s;TDMSTPortNumber=%s;Charset=UTF8', $host, $port),
            'driver'   => 'odbc',
            'username' => $user,
            'password' => $password,
        ]);
    }
}
