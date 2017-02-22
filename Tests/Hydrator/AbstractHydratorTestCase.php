<?php

namespace DoL\LdapBundle\Tests\Hydrator;

use DoL\LdapBundle\Tests\DependencyInjection\ConfigurationTrait;

abstract class AbstractHydratorTestCase extends \PHPUnit_Framework_TestCase
{
    use ConfigurationTrait {
        getDefaultUserConfig as parentGetDefaultUserConfig;
    }
    use HydratorInterfaceTestTrait;

    /**
     * Returns default configuration for User subtree.
     *
     * Same as service parameter `dol_ldap.ldap_manager.parameters`
     *
     * @return array
     */
    protected function getDefaultUserConfig()
    {
        $config = $this->parentGetDefaultUserConfig();
        $config['attributes'][] = [
            'ldap_attr' => 'roles',
            'user_method' => 'setRoles',
        ];

        return $config;
    }
}
