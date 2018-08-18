<?php

declare(strict_types=1);

namespace Keboola\ExTeradata;

use Dibi\Connection;
use Keboola\Component\UserException;

class Extractor
{
    /** @var Connection */
    private $connection;

    /** @var string */
    private $database;

    public function __construct(Connection $connection, string $database)
    {
        $this->connection = $connection;
        $this->database = $database;
    }

    /**
     * @param string $tableName
     *
     * @return array
     * @throws UserException
     * @throws \Throwable
     */
    public function extractTable(string $tableName): array
    {
        $sql = sprintf('SELECT * FROM %s.%s', $this->database, $tableName);

        try {
            $result = $this->connection->query($sql)->fetchAll();
        } catch (\Throwable $exception) {
            if (preg_match('~Object \'.+\..+\' does not exist.~', $exception->getMessage())) {
                throw new UserException(sprintf(
                    'Table \'%s\' does not exist in database \'%s\'.',
                    $tableName,
                    $this->database
                ));
            } elseif (preg_match('~Database \'.+\' does not exist.~', $exception->getMessage())) {
                throw new UserException(sprintf(
                    'Database \'%s\' does not exist.',
                    $this->database
                ));
            } else {
                throw $exception;
            }
        }

        if (empty($result)) {
            throw new UserException(sprintf('There are no rows in table \'%s.%s\'.', $this->database, $tableName));
        }

        return $result;
    }
}
