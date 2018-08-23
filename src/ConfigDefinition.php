<?php

declare(strict_types=1);

namespace Keboola\ExTeradata;

use Keboola\Component\Config\BaseConfigDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class ConfigDefinition extends BaseConfigDefinition
{
    protected function getParametersDefinition(): ArrayNodeDefinition
    {
        $parametersNode = parent::getParametersDefinition();
        // @formatter:off
        /** @noinspection NullPointerExceptionInspection */
        $parametersNode
            ->children()
                ->arrayNode('db')
                    ->isRequired()
                    ->children()
                        ->scalarNode('host')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('username')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('#password')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('database')->isRequired()->cannotBeEmpty()->end()
                    ->end()
                ->end()
                ->arrayNode('tables')
                    ->requiresAtLeastOneElement()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('name')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('query')->cannotBeEmpty()->end()
                            ->scalarNode('outputTable')->isRequired()->cannotBeEmpty()->end()
                            ->booleanNode('incremental')->defaultValue(false)->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
        // @formatter:on
        return $parametersNode;
    }
}
