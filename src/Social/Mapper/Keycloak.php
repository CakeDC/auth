<?php
declare(strict_types=1);

/**
 * Copyright 2010 - 2026, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010 - 2026, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Auth\Social\Mapper;

use Cake\Core\Configure;
use Cake\Utility\Hash;
use UnexpectedValueException;

/**
 * Keycloak Mapper
 *
 * The `roles` field holds the application role mapped from the Keycloak realm roles,
 * using the `rolesMap` provider config (`OAuth.providers.keycloak.rolesMap`). Without a
 * roles map the `roles` field is null.
 */
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
        'full_name' => 'name',
        'email' => 'email',
        'username' => 'preferred_username',
        'id' => 'sub',
        'link' => 'website',
        'roles' => 'realm_access.roles',
        'validated' => 'email_verified',
    ];

    /**
     * Map Keycloak realm roles to application roles. The first matching entry wins.
     *
     * @var array<string, string>
     */
    protected array $_rolesMap = [];

    /**
     * Constructor
     *
     * @param mixed $mapFields map fields merged over the Keycloak map, defaults to `OAuth.providers.keycloak.mapFields`
     * @param array<string, string>|null $rolesMap roles map, defaults to `OAuth.providers.keycloak.rolesMap`
     */
    public function __construct(mixed $mapFields = null, ?array $rolesMap = null)
    {
        $mapFields ??= Configure::read('OAuth.providers.keycloak.mapFields') ?: null;
        $rolesMap ??= Configure::read('OAuth.providers.keycloak.rolesMap');
        if (is_array($rolesMap) && $rolesMap) {
            $this->_rolesMap = $rolesMap;
        }
        // A partial map must not drop the `id`, `roles` and `validated` fields the role check depends on
        if (is_array($mapFields)) {
            $mapFields = array_merge($this->_mapFields, $mapFields);
        }
        parent::__construct($mapFields);
    }

    /**
     * Get the application role from the Keycloak realm roles
     *
     * Requires the realm roles mapper of the `roles` client scope to have "Add to userinfo" enabled.
     * Role names are case-sensitive, as in Keycloak.
     *
     * @param mixed $rawData raw data
     * @throws \UnexpectedValueException when a roles map is set and there are no roles or none of them is mapped
     * @return string|null The mapped role, null when no roles map is set
     */
    protected function _roles(mixed $rawData): ?string
    {
        if (!$this->_rolesMap) {
            return null;
        }
        $keycloakRoles = Hash::get($rawData, $this->_mapFields['roles']);
        if (!is_array($keycloakRoles)) {
            throw new UnexpectedValueException(
                'No roles in Keycloak userinfo. Enable "Add to userinfo" for the realm roles mapper ' .
                'or check the `roles` map field',
            );
        }

        foreach ($this->_rolesMap as $keycloakRole => $role) {
            foreach ($keycloakRoles as $userRole) {
                if ($userRole === $keycloakRole) {
                    return $role;
                }
            }
        }

        throw new UnexpectedValueException(sprintf(
            'No mapped role found in Keycloak roles. Mapped roles: %s',
            implode(', ', array_keys($this->_rolesMap)),
        ));
    }

    /**
     * Keycloak reports the email verification status
     *
     * @param mixed $rawData raw data
     * @return bool
     */
    protected function _validated(mixed $rawData): bool
    {
        return (bool)Hash::get($rawData, $this->_mapFields['validated']);
    }
}
