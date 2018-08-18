<?php

declare(strict_types=1);

namespace Keboola\ExTeradata;

use Keboola\Component\Config\BaseConfig;

class Config extends BaseConfig
{
    public function getHost() : string
    {
        return $this->getValue(['parameters', 'host']);
    }

    public function getUser() : string
    {
        return $this->getValue(['parameters', 'user']);
    }

    public function getPassword() : string
    {
        return $this->getValue(['parameters', '#password']);
    }
}
