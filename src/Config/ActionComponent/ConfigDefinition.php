<?php

declare(strict_types=1);

namespace Keboola\ExTeradata\Config\ActionComponent;

use Keboola\ExTeradata\Config\BaseConfigDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class ConfigDefinition extends BaseConfigDefinition
{
    public function getParametersDefinition(): ArrayNodeDefinition
    {
        $parametersNode = parent::getParametersDefinition();
        // @formatter:off
        /** @noinspection NullPointerExceptionInspection */
        $parametersNode
            ->children()
                ->append($this->getDbNode());

        // @formatter:on
        return $parametersNode;
    }
}
