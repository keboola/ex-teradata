<?php

declare(strict_types=1);

namespace Keboola\ExTeradata;

use Keboola\Component\UserException;
use Keboola\ExTeradata\Response\Column;

class ExtractorHelper
{
    public function validateObject(string $object): void
    {
        if (str_contains($object, '"')) {
            throw new UserException(sprintf('Object "%s" contain restricted character \'"\'.', $object));
        }
    }

    /**
     * @param string[] $columns
     */
    public function getExportSql(string $database, string $tableName, ?array $columns): string
    {
        $this->validateObject($database);
        $this->validateObject($tableName);

        if ($columns === null || $columns === []) {
            $objects = '*';
        } else {
            $columns = array_map(
                function ($column): string {
                    $this->validateObject($column);
                    return sprintf('"%s"', $column);
                },
                $columns,
            );

            $objects = implode(',', $columns);
        }

        return sprintf('SELECT %s FROM "%s"."%s"', $objects, $database, $tableName);
    }
}
