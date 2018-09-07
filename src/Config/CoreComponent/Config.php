<?php

declare(strict_types=1);

namespace Keboola\ExTeradata\Config\CoreComponent;

use Keboola\Component\Config\BaseConfig;

class Config extends BaseConfig
{
    public function getHost(): string
    {
        return $this->getValue(['parameters', 'db', 'host']);
    }

    public function getUser(): string
    {
        return $this->getValue(['parameters', 'db', 'user']);
    }

    public function getPassword(): string
    {
        return $this->getValue(['parameters', 'db', '#password']);
    }

    public function getOutputTable(): string
    {
        return $this->getValue(['parameters', 'outputTable']);
    }

    public function getQuery(): ?string
    {
        try {
            return $this->getValue(['parameters', 'query']);
        } catch (\InvalidArgumentException $exception) {
            return null;
        }
    }

    public function getSchema(): ?string
    {
        try {
            return $this->getValue(['parameters', 'table', 'schema']);
        } catch (\InvalidArgumentException $exception) {
            return null;
        }
    }

    public function getTableName(): ?string
    {
        try {
            return $this->getValue(['parameters', 'table', 'tableName']);
        } catch (\InvalidArgumentException $exception) {
            return null;
        }
    }

    public function getColumns(): array
    {
        return $this->getValue(['parameters', 'columns']);
    }

    public function getIncremental(): bool
    {
        return $this->getValue(['parameters', 'incremental']);
    }

    public function getPrimaryKey(): array
    {
        return $this->getValue(['parameters', 'primaryKey']);
    }
}
