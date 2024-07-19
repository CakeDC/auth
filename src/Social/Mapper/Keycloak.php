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
        'roles' => 'realm_access'
    ];
    protected string $_rolesMatch = 'CakeDc-';


    function _roles(array $data) :string
    {   # Client Scopes > roles > Mappers > realm roles -> Add to userinfo  := Enable
        if (is_null($data[$this->_mapFields['roles']])){
            throw new \Exception("No roles in UserInfo token. Set realm roles 'Add to userinfo' field to ON in Client scopes or check the roles field in _mapFields");  
        }
        $roles = array_filter($data[$this->_mapFields['roles']]['roles'], 
            function ($var) { return (strpos($var, $this->_rolesMatch) !== false); });
            
        if (empty($roles)){
            throw new \Exception("No CakeDc-* role mapped in keycloak");  
        }
        $role= str_ireplace($this->_rolesMatch, '',array_pop($roles));    
        #
        # Set the cakedc default user role from keycloak roles
        #
        Configure::write('Users.Registration.defaultRole',$role);
        Configure::write('Users.Registration.KeycloakRole',$role);
        return $role;   
    }
}
