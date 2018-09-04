<?php

declare(strict_types=1);

namespace Keboola\ExTeradata;

use Keboola\Component\BaseComponent;
use Keboola\ExTeradata\Config\ActionComponent\Config;
use Keboola\ExTeradata\Config\ActionComponent\ConfigDefinition;
use Keboola\ExTeradata\Factories\ConnectionFactory;

class ActionComponent extends BaseComponent
{
    public function run(): void
    {
        /** @var Config $config */
        $config = $this->getConfig();

        $exceptionHandler = new ExceptionHandler();

        try {
            $connection = (new ConnectionFactory())->create(
                $config->getHost(),
                $config->getUser(),
                $config->getPassword()
            );
        } catch (\Throwable $exception) {
            throw $exceptionHandler->createException($exception);
        }

        switch ($config->getAction()) {
            case 'testConnection':
                $connection->query("SELECT 1");
                print json_encode(['success' => 'ok'], JSON_PRETTY_PRINT);
                break;
        }
    }

    protected function getConfigClass(): string
    {
        return Config::class;
    }

    protected function getConfigDefinitionClass(): string
    {
        return ConfigDefinition::class;
    }
}
