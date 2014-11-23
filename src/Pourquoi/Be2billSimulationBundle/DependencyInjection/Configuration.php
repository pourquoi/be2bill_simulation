<?php

namespace Pourquoi\Be2billSimulationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('pourquoi_be2bill_simulation')
        ->children()
            ->scalarNode('template_url')
                ->defaultNull()
            ->end()
            ->scalarNode('template_mobile_url')
                ->defaultNull()
            ->end()
            ->scalarNode('notification_url')
                ->defaultNull()
            ->end()
            ->scalarNode('identifier')
                ->isRequired()
                ->cannotBeEmpty()
            ->end()
            ->scalarNode('password')
                ->isRequired()
                ->cannotBeEmpty()
            ->end()
            ->scalarNode('return_url')
                ->defaultNull()
            ->end()
        ->end();


        return $treeBuilder;
    }
}
