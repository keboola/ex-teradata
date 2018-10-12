<?php

namespace Keboola\ExTeradata\Config;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class BaseConfigDefinition extends \Keboola\Component\Config\BaseConfigDefinition
{
    protected function getDbNode(): ArrayNodeDefinition
    {
        $builder = new TreeBuilder();

        /** @var ArrayNodeDefinition $node */
        $node = $builder->root('db');

        // @formatter:off
        $node
            ->isRequired()
            ->addDefaultsIfNotSet()
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
            ->end();
        // @formatter:on

        return $node;
    }
}
