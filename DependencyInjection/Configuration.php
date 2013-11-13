<?php

namespace DoL\LdapBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 *
 * @author DarwinOnLine
 * @author Maks3w
 * @link https://github.com/DarwinOnLine/DoLLdapBundle
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('dol_ldap');

        $rootNode
            ->children()
                ->arrayNode('domains')
                    ->useAttributeAsKey('id')
                    ->prototype('array')
                        ->children()
                            ->arrayNode('driver')
                                ->children()
                                    ->scalarNode('host')->isRequired()->cannotBeEmpty()->end()
                                    ->scalarNode('port')->defaultValue(389)->end()
                                    ->scalarNode('useStartTls')->defaultFalse()->end()
                                    ->scalarNode('useSsl')->defaultFalse()->end()
                                    ->scalarNode('username')->end()
                                    ->scalarNode('password')->end()
                                    ->scalarNode('bindRequiresDn')->defaultFalse()->end()
                                    ->scalarNode('baseDn')->end()
                                    ->scalarNode('accountCanonicalForm')->end()
                                    ->scalarNode('accountDomainName')->end()
                                    ->scalarNode('accountDomainNameShort')->end()
                                    ->scalarNode('accountFilterFormat')->end()
                                    ->scalarNode('allowEmptyPassword')->end()
                                    ->scalarNode('optReferrals')->end()
                                    ->scalarNode('tryUsernameSplit')->end()
                                    ->scalarNode('networkTimeout')->end()
                                ->end()
                                ->validate()
                                    ->ifTrue(function($v) {
                                        return $v['useSsl'] && $v['useStartTls'];
                                    })
                                    ->thenInvalid('The useSsl and useStartTls options are mutually exclusive.')
                                ->end()
                            ->end()
                            ->arrayNode('user')
                                ->children()
                                    ->scalarNode('baseDn')->isRequired()->cannotBeEmpty()->end()
                                    ->scalarNode('filter')->defaultValue('')->end()
                                    ->arrayNode('attributes')
                                        ->defaultValue(array(
                                            array(
                                                'ldap_attr'   => 'uid',
                                                'user_method' => 'setUsername')
                                            ))
                                        ->prototype('array')
                                            ->children()
                                                ->scalarNode('ldap_attr')->isRequired()->cannotBeEmpty()->end()
                                                ->scalarNode('user_method')->isRequired()->cannotBeEmpty()->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        $this->addServiceSection($rootNode);

        return $treeBuilder;
    }

    private function addServiceSection(ArrayNodeDefinition $node)
    {
        $node
                ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('service')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('user_manager')->defaultValue('fos_user.user_manager')->end()
                                ->scalarNode('ldap_manager')->defaultValue('dol_ldap.ldap_manager.default')->end()
                                ->scalarNode('ldap_driver')->defaultValue('dol_ldap.ldap_driver.zend')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end();
    }
}
