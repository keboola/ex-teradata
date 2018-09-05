<?php

declare(strict_types=1);

namespace Keboola\ExTeradata\Response;

class Column implements \JsonSerializable
{
    /** @var string */
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
