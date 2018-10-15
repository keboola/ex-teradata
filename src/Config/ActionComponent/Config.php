<?php

declare(strict_types=1);

namespace Keboola\ExTeradata\Config\ActionComponent;

use Keboola\Component\Config\BaseConfig;

class Config extends BaseConfig
{
    public function getHost(): string
    {
        return $this->getValue(['parameters', 'db', 'host']);
    }

    public function getPort(): int
    {
        return $this->getValue(['parameters', 'db', 'port']);
    }

    public function getUser(): string
    {
        return $this->getValue(['parameters', 'db', 'user']);
    }

    public function getPassword(): string
    {
        return $this->getValue(['parameters', 'db', '#password']);
    }

    public function getDatabase(): string
    {
        return $this->getValue(['parameters', 'db', 'database']);
    }
}
