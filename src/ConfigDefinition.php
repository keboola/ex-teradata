<?php

declare(strict_types=1);

namespace Keboola\ExTeradata;

use Keboola\Component\Config\BaseConfigDefinition;
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
                ->arrayNode('db')
                    ->isRequired()
                    ->children()
                        ->scalarNode('host')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('username')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('#password')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('database')->isRequired()->cannotBeEmpty()->end()
                    ->end()
                ->end()
            ->integerNode('id')->isRequired()->min(0)->end()
            ->scalarNode('name')->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('query')->end()
            ->arrayNode('table')
                ->children()
                    ->scalarNode('schema')->end()
                    ->scalarNode('tableName')->end()
                ->end()
            ->end()
            ->arrayNode('columns')
                ->prototype('scalar')->end()
            ->end()
            ->scalarNode('outputTable')->isRequired()->cannotBeEmpty()->end()
            ->booleanNode('incremental')->defaultValue(false)->end()
            ->booleanNode('enabled')->defaultValue(true)->end()
            ->arrayNode('primaryKey')
                ->prototype('scalar')->end()
            ->end()
            ->integerNode('retries')->min(1)->end()
            ->booleanNode('advancedMode')->end()
        ;
        // @formatter:on
        return $parametersNode;
    }
}
