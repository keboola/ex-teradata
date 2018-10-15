<?php

declare(strict_types=1);

namespace Keboola\ExTeradata\Config\CoreComponent;

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
                        ->integerNode('port')->defaultValue(1025)->end()
                        ->scalarNode('user')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('#password')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('database')->isRequired()->cannotBeEmpty()->end()
                        ->arrayNode('ssh')
                            ->children()
                                ->booleanNode('enabled')->end()
                                ->arrayNode('keys')
                                    ->children()
                                        ->scalarNode('private')->end()
                                        ->scalarNode('#private')->end()
                                        ->scalarNode('public')->end()
                                    ->end()
                                ->end()
                                ->scalarNode('sshHost')->end()
                                ->scalarNode('sshPort')->end()
                                ->scalarNode('remoteHost')->end()
                                ->scalarNode('remotePort')->end()
                                ->scalarNode('localPort')->end()
                                ->scalarNode('user')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->scalarNode('query')
            ->end()
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
            ->arrayNode('primaryKey')
                ->prototype('scalar')->end()
            ->end()
            ->integerNode('retries')->min(1)->end()
            ->booleanNode('advancedMode')->end()
        ;

        $parametersNode->validate()
            ->ifTrue(function ($v) {
                return !isset($v['query'])
                    && (
                        !isset($v['table']['tableName'])
                        || !isset($v['table']['schema'])
                    );
            })
            ->thenInvalid('The \'query\' or \'table.schema\' with \'table.tableName\' option is required.')
            ->end();

        // @formatter:on
        return $parametersNode;
    }
}
