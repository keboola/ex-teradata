<?php

declare(strict_types=1);

namespace Keboola\ExTeradata;

use Dibi\Connection;

class ConnectionFactory
{
    public function create(string $host, string $user, string $password): Connection
    {
        return new Connection([
            'dsn' => sprintf('DRIVER={Teradata};DBCName=%s', $host),
            'driver'   => 'odbc',
            'username' => $user,
            'password' => $password,
        ]);
    }
}
