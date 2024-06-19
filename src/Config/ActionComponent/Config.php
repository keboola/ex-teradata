<?php

declare(strict_types=1);

namespace Keboola\ExTeradata\Config\ActionComponent;

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

    public function getDatabase(): string
    {
        return $this->getStringValue(['parameters', 'db', 'database']);
    }
}
