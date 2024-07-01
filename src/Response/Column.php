<?php

declare(strict_types=1);

namespace Keboola\ExTeradata\Response;

use JsonSerializable;

class Column implements JsonSerializable
{
    public function __construct(private string $name)
    {
    }

    /**
     * {@inheritDoc}
     * @return array{name:string}
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
