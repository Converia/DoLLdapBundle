<?php

namespace DoL\LdapBundle\Ldap;

use DoL\LdapBundle\Driver\LdapDriverInterface;
use DoL\LdapBundle\Model\LdapUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;

/**
 * Ldap manager for multi-domains
 * 
 * @author DarwinOnLine
 * @author Maks3w
 * @link https://github.com/DarwinOnLine/DoLLdapBundle
 */
class LdapManager implements LdapManagerInterface
{
    protected $driver;
    protected $userManager;
    protected $paramSets = array();

    protected $params = array();
    protected $ldapAttributes = array();
    protected $ldapUsernameAttr;

    public function __construct(LdapDriverInterface $driver, $userManager, array $paramSets)
    {
        $this->driver = $driver;
        $this->userManager = $userManager;
        $this->paramSets = $paramSets;
    }

    /**
     * {@inheritDoc}
     */
    public function bind(UserInterface $user, $password)
    {
        if ( !empty($this->params) )
        {
            return $this->driver->bind($user, $password);
        }
        else
        {
            foreach ( $this->paramSets as $paramSet )
            {
                $this->driver->init($paramSet['driver']);

                if ( false !== $this->driver->bind($user, $password) )
                {
                    $this->params = $paramSet['user'];
                    $this->setLdapAttr();

                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * {@inheritDoc}
     */
    public function findUserByUsername($username)
    {
        if ( !empty($this->params) )
        {
            return $this->findUserBy(array($this->ldapUsernameAttr => $username));
        }
        else
        {
            foreach ( $this->paramSets as $paramSet )
            {
                $this->driver->init($paramSet['driver']);
                $this->params = $paramSet['user'];
                $this->setLdapAttr();
                
                $user = $this->findUserBy(array($this->ldapUsernameAttr => $username));
                if ( false !== $user && $user instanceof UserInterface )
                {
                    return $user;
                }
                
                $this->params = array();
                $this->setLdapAttr();
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findUserBy(array $criteria)
    {
        $filter  = $this->buildFilter($criteria);
        $entries = $this->driver->search($this->params['baseDn'], $filter, $this->ldapAttributes);
        if ($entries['count'] > 1) {
            throw new \Exception('This search can only return a single user');
        }

        if ($entries['count'] == 0) {
            return false;
        }
        $user = $this->userManager->createUser();
        $this->hydrate($user, $entries[0]);

        return $user;
    }

    private function setLdapAttr()
    {
        if ( isset($this->params['attributes']) )
        {
            foreach ($this->params['attributes'] as $attr) {
                $this->ldapAttributes[] = $attr['ldap_attr'];
            }

            $this->ldapUsernameAttr = $this->ldapAttributes[0];
        }
        else
        {
            $this->ldapAttributes = array();
            $this->ldapUsernameAttr = null;
        }
    }

    /**
     * Build Ldap filter
     *
     * @param  array  $criteria
     * @param  string $condition
     * @return string
     */
    protected function buildFilter(array $criteria, $condition = '&')
    {
        $criteria = self::escapeValue($criteria);
        $filters = array();
        $filters[] = $this->params['filter'];
        foreach ($criteria as $key => $value) {
            $filters[] = sprintf('(%s=%s)', $key, $value);
        }

        return sprintf('(%s%s)', $condition, implode($filters));
    }

    /**
     * Hydrates an user entity with ldap attributes.
     *
     * @param  UserInterface $user  user to hydrate
     * @param  array         $entry ldap result
     *
     * @return UserInterface
     */
    protected function hydrate(UserInterface $user, array $entry)
    {
        $user->setPassword('');

        if ($user instanceof AdvancedUserInterface) {
            $user->setEnabled(true);
        }

        foreach ($this->params['attributes'] as $attr) {
            $ldapValue = $entry[$attr['ldap_attr']];
            $value = null;

            if (!array_key_exists('count', $ldapValue) ||  $ldapValue['count'] == 1) {
                $value = $ldapValue[0];
            } else {
                $value = array_slice($ldapValue, 1);
            }

            call_user_func(array($user, $attr['user_method']), $value);
        }

        if ($user instanceof LdapUserInterface) {
            $user->setDn($entry['dn']);
        }
    }


    


    /**
     * Get a list of roles for the username.
     *
     * @param string $username
     * @return array
     */
    public function getRolesForUsername($username)
    {

    }

    /**
     * Escapes the given VALUES according to RFC 2254 so that they can be safely used in LDAP filters.
     *
     * Any control characters with an ASCII code < 32 as well as the characters with special meaning in
     * LDAP filters "*", "(", ")", and "\" (the backslash) are converted into the representation of a
     * backslash followed by two hex digits representing the hexadecimal value of the character.
     * @see Net_LDAP2_Util::escape_filter_value() from Benedikt Hallinger <beni@php.net>
     * @link http://pear.php.net/package/Net_LDAP2
     * @author Benedikt Hallinger <beni@php.net>
     *
     * @param  string|array $values Array of values to escape
     * @return array Array $values, but escaped
     */
    public static function escapeValue($values = array())
    {
        if (!is_array($values))
            $values = array($values);
        foreach ($values as $key => $val) {
            // Escaping of filter meta characters
            $val = str_replace(array('\\', '*', '(', ')'), array('\5c', '\2a', '\28', '\29'), $val);
            // ASCII < 32 escaping
            $val = Converter::ascToHex32($val);
            if (null === $val) {
                $val          = '\0';  // apply escaped "null" if string is empty
            }
            $values[$key] = $val;
        }

        return (count($values) == 1 && array_key_exists(0, $values)) ? $values[0] : $values;
    }

    /**
     * Undoes the conversion done by {@link escapeValue()}.
     *
     * Converts any sequences of a backslash followed by two hex digits into the corresponding character.
     * @see Net_LDAP2_Util::escape_filter_value() from Benedikt Hallinger <beni@php.net>
     * @link http://pear.php.net/package/Net_LDAP2
     * @author Benedikt Hallinger <beni@php.net>
     *
     * @param  string|array $values Array of values to escape
     * @return array Array $values, but unescaped
     */
    public static function unescapeValue($values = array())
    {
        if (!is_array($values))
            $values = array($values);
        foreach ($values as $key => $value) {
            // Translate hex code into ascii
            $values[$key] = Converter::hex32ToAsc($value);
        }

        return (count($values) == 1 && array_key_exists(0, $values)) ? $values[0] : $values;
    }
}
