<?php

declare(strict_types=1);

namespace Keboola\ExTeradata\Config\CoreComponent;

use InvalidArgumentException;
use Keboola\Component\Config\BaseConfig;

class Config extends BaseConfig
{
    public function getHost(): string
    {
        return $this->getStringValue(['parameters', 'db', 'host']);
    }

    public function getPort(): int
    {
        return $this->getIntValue(['parameters', 'db', 'port']);
    }

    public function getUser(): string
    {
        return $this->getStringValue(['parameters', 'db', 'user']);
    }

    public function getPassword(): string
    {
        return $this->getStringValue(['parameters', 'db', '#password']);
    }

    public function getOutputTable(): string
    {
        return $this->getStringValue(['parameters', 'outputTable']);
    }

    public function getQuery(): ?string
    {
        try {
            return $this->getStringValue(['parameters', 'query']);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    public function getSchema(): ?string
    {
        try {
            return $this->getStringValue(['parameters', 'table', 'schema']);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    public function getTableName(): ?string
    {
        try {
            return $this->getStringValue(['parameters', 'table', 'tableName']);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * @return string[]
     */
    public function getColumns(): array
    {
        return $this->getArrayValue(['parameters', 'columns']);
    }

    public function getIncremental(): bool
    {
        return $this->getValue(['parameters', 'incremental']);
    }

    /**
     * @return array<mixed>
     */
    public function getPrimaryKey(): array
    {
        return $this->getArrayValue(['parameters', 'primaryKey']);
    }
}
