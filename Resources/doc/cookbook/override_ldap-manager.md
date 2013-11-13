Override Ldap Manager
=====================

You could easy customize Ldap Manager with a version adapted to your needs.

### Customize hydrate process

This example show how to set the mail field blank for Users object provided by
FOSUserBundle

The hydrate function fill User class attributes with the attributes retrieved
from Ldap.

**Configure LdapBundle with your service**

``` yaml
# app/config/config.yml

dol_ldap:
    # ...
    service:
        ldap_manager:  acme.ldap.ldap_manager
````

**Setup the service in your own bundle**

```` yml
# src/Acme/DemoBundle/Resources/config/services.yml
parameters:
  acme.ldap.ldap_manager.class: Acme\DemoBundle\Ldap\LdapManager

services:
  acme.ldap.ldap_manager:
    class: '%acme.ldap.ldap_manager.class%'
    arguments: [ '@dol_ldap.ldap_driver', '@dol_ldap.user_manager', '%dol_ldap.domains.parameters%' ]
````

If you prefer XML :

```` xml
<!-- src/Acme/DemoBundle/Resources/config/services.xml -->
<container xmlns="http://symfony.com/schema/dic/services"
           xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
           xsi:schemaLocation="http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd">

    <services>

        <!-- ... -->

        <service id="acme.ldap.ldap_manager" class=Acme\DemoBundle\Ldap\LdapManager">
            <argument type="service" id="dol_ldap.driver" />
            <argument type="service" id="dol_ldap.user_manager" />
            <argument>%dol_ldap.domains.parameters%</argument>
        </service>

        <!-- ... -->

    </services>

</container>
````

**Extends LdapManager and customize him**

```` php
// src/Acme/DemoBundle/Ldap/LdapManager.php
<?php

namespace Acme\DemoBundle\Ldap;

use DoL\LdapBundle\Ldap\LdapManager as BaseLdapManager;
use DoL\LdapBundle\Model\LdapUserInterface;

class LdapManager extends BaseLdapManager
{
    protected function hydrate(LdapUserInterface $user, array $entry)
    {
        parent::hydrate($user, $entry);

        // Your custom code
        $user->setEmail('');
        $user->setEmailCanonical('');
    }
}
````
