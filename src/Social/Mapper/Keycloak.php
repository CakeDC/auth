<?php
declare(strict_types=1);

/**
 * Copyright 2010 - 2019, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010 - 2019, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */


namespace CakeDC\Auth\Social\Mapper;
use Cake\Core\Configure;

class Keycloak extends AbstractMapper
{
    /**
     * Map for provider fields
     *
     * @var array
     */
    protected array $_mapFields = [
        'first_name' => 'given_name',
        'last_name' => 'family_name',
        'email' => 'email',
        'username' => 'preferred_username',
        'id' => 'sub',
        'link' => 'website',
        'roles' => 'realm_access',
        'validated' => 'email_verified'
    ];
    
    /**
     * Map Keycloak roles to CakeDC roles
     *
     * @var array
     */
    protected array $_rolesMap = [
        'CakeDc-Admin' => 'admin',
        'CakeDc-User' => 'user',
        'CakeDc-Worker' => 'user'
    ];
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $configRoleMap = Configure::read('OAuth.providers.keycloak.rolesMap');
        $configMapFields = Configure::read('OAuth.providers.keycloak.mapFields');
        if (!empty($configRoleMap) && is_array($configRoleMap)) {
            $this->_rolesMap = $configRoleMap;
        }
        if (!empty($configMapFields) && is_array($configMapFields)) {
            $this->_mapFields = $configMapFields;
        }
    }

    function _roles(array $data): string
    {   // Client Scopes > roles > Mappers > realm roles -> Add to userinfo  := Enable
        if (is_null($data[$this->_mapFields['roles']])) {
            throw new \Exception("No roles in UserInfo token. Set realm roles 'Add to userinfo' field to ON in Client scopes or check the roles field in _mapFields");
        }
        
        $keycloakRoles = $data[$this->_mapFields['roles']]['roles'];
        
        // Ignore case when comparing roles
        $mappedRoles = [];
        foreach ($keycloakRoles as $keycloakRole) {
            foreach (array_keys($this->_rolesMap) as $mapKey) {
                if (strcasecmp($keycloakRole, $mapKey) === 0) {
                    $mappedRoles[] = $mapKey;
                    break;
                }
            }
        }
            
        if (empty($mappedRoles)) {
            throw new \Exception("No mappable role found in Keycloak. Available roles in map: " . implode(', ', array_keys($this->_rolesMap)) . ' / '. implode(', ', ($keycloakRoles)));
        }
        
        $keycloakRole = array_pop($mappedRoles);
        $role = $this->_rolesMap[$keycloakRole];
        // Set the cakedc default user role from keycloak roles
        Configure::write('Users.Registration.defaultRole', $role);
        return $role;   
    }
}
