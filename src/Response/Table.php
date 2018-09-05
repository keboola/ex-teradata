<?php

declare(strict_types=1);

namespace Keboola\ExTeradata\Response;

class Table implements \JsonSerializable
{
    /** @var string */
    private $schema;

    /** @var string */
    private $tableName;

    /** @var array|\JsonSerializable[] */
    private $columns;

    // private $autoIncrement;

    // private $rowCount;

    public function __construct(
        string $schema,
        string $tableName,
        array $columns
        // int $autoIncrement,
        // int $rowCount
    ) {
        $this->schema = $schema;
        $this->tableName = $tableName;
        $this->columns = $columns;
        // $this->autoIncrement = $autoIncrement;
        // $this->rowCount = $rowCount;
    }

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
