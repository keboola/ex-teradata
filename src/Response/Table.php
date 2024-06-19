<?php

declare(strict_types=1);

namespace Keboola\ExTeradata\Response;

use JsonSerializable;

class Table implements JsonSerializable
{
    // private $autoIncrement;

    // private $rowCount;

    /**
     * @param Column[] $columns
     */
    public function __construct(
        private string $schema,
        private string $tableName,
        private array $columns = [],
        // int $autoIncrement,
        // int $rowCount
    ) {
        // $this->autoIncrement = $autoIncrement;
        // $this->rowCount = $rowCount;
    }

    public function addColumn(Column $column): void
    {
        $this->columns[] = $column;
    }

    /**
     * {@inheritDoc}
     * @return array{
     *     schema: string,
     *     name: string,
     *     columns: Column[]
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'schema' => $this->schema,
            'name' => $this->tableName,
            'columns' => $this->columns,
            // 'autoIncrement' => $this->autoIncrement,
            // 'rowCount' => $this->rowCount,
        ];
    }
}
