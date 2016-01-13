<?php

namespace ArcoVoltaico\ImportBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('arco_voltaico_import');

//http://symfony.com/doc/current/components/config/definition.html

        $rootNode
            ->children()
                ->scalarNode('namespace')->end()
                ->scalarNode('bundle')->end()
                ->arrayNode('entities')
                    ->prototype('array') //several entities
                        ->children() //each entity
                            ->booleanNode('clear')->defaultFalse()->end()
                            ->booleanNode('upload')->defaultFalse()->end()
                            ->scalarNode('parent')->end()
                            ->variableNode('multiple')->end()
                             ->scalarNode('path')->end()
                            ->arrayNode('import')
                                ->prototype('array') //several fields
                                    ->children() //each field
                                        ->variableNode('xml')->end() //can be an array or a single string
                                        ->booleanNode('nullable')->defaultTrue()->end()
                                        ->arrayNode('map') //mapping
                                            ->prototype('scalar') ->end()//each map field
                                         ->end()//arraynode map
                                        ->scalarNode('class')->end()
                                        ->arrayNode('activate') //activate
                                         ->prototype('scalar') ->end()//each activate field
                                        ->end()//activate map
                                         ->arrayNode('mirror') //the class value depending the current multiple 
                                                        ->prototype('scalar') ->end()//each map field
                                        ->end()//arraynode map
                                    ->end()//children field
                                ->end()//proto array
                            ->end()//array fields
                        ->end() //children entity
                    ->end() //proto array
                ->end() // entities
            ->end() //children
        ;



        return $treeBuilder;
    }
}
