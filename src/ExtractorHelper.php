<?php

declare(strict_types=1);

namespace Keboola\ExTeradata;

use Keboola\Component\UserException;

class ExtractorHelper
{
    public function validateObject(string $object): void
    {
        if (strpos($object, '"') !== false) {
            throw new UserException(sprintf('Object \'%s\' contain restricted character \'"\'.', $object));
        }
    }

    public function getExportSql(string $database, string $tableName, ?array $columns): string
    {
        $this->validateObject($database);
        $this->validateObject($tableName);

        $columns = array_map(
            function ($column) {
                $this->validateObject($column);
                return sprintf('"%s"', $column);
            },
            $columns
        );

        if ($columns) {
            $objects = implode(',', $columns);
        } else {
            $objects = '*';
        }

        return sprintf('SELECT %s FROM "%s"."%s"', $objects, $database, $tableName);
    }
}
