<?php

declare(strict_types=1);

namespace Keboola\ExTeradata\Config\ActionComponent;

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
                        ->scalarNode('port')->defaultValue(1025)->end()
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
                ->end();

        // @formatter:on
        return $parametersNode;
    }
}
