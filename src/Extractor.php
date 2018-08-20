<?php

declare(strict_types=1);

namespace Keboola\ExTeradata;

use Dibi\Connection;
use Keboola\Component\UserException;

class Extractor
{
    /** @var Connection */
    private $connection;

    /** @var ExceptionHandler */
    private $exceptionHandler;

    /** @var string */
    private $database;

    public function __construct(Connection $connection, ExceptionHandler $exceptionHandler, string $database)
    {
        $this->connection = $connection;
        $this->exceptionHandler = $exceptionHandler;
        $this->database = $database;
    }

    public function extractTable(string $tableName): \Iterator
    {
        $sql = sprintf('SELECT * FROM %s.%s', $this->database, $tableName);

        try {
            $sth = $this->connection->query($sql);
            while ($row = $sth->fetch()) {
                yield $row;
            }
        } catch (\Throwable $exception) {
            $this->exceptionHandler->handleExtractorException($exception, $this->database, $tableName);
        }
    }
}
