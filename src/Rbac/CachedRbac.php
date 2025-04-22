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

namespace CakeDC\Auth\Rbac;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\InstanceConfigTrait;
use Cake\Log\LogTrait;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use CakeDC\Auth\Rbac\Permissions\AbstractProvider;
use CakeDC\Auth\Rbac\PermissionMatchResult;
use CakeDC\Auth\Rbac\Rules\Rule;
use CakeDC\Auth\Rbac\Rules\RuleRegistry;
use CakeDC\Auth\Rbac\RbacInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LogLevel;

/**
 * Class CachedRbac, determine if a request matches any of the given rbac rules
 *
 * @package Rbac
 */
class CachedRbac extends Rbac
{
    /**
     * A map of rules
     *
     * @var array[] rules array
     */
    protected $permissionsMap = [];

    /**
     * An undasherize flag
     *
     * @var bool undasherize flag
     */
    protected bool $undasherize = false;

    /**
     * Rbac constructor.
     *
     * @param array $config Class configuration
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
        if (isset($config['undasherize'])) {
            $this->undasherize = $config['undasherize'];
        }
        $this->permissionsMap = Cache::remember('auth_permissions', function () {
            return $this->buildPermissionsMap();
        }, '_cakedc_auth_');
    }

    /**
     * @return array
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * @return array
     */
    public function buildPermissionsMap()
    {
		$asArray = function ($permission, $key, $default = null) {
            if ($default !== null && !array_key_exists($key, $permission)) {
				return [$default, '_'];
            }
			if (!array_key_exists($key, $permission) || $permission[$key] == false || $permission[$key] == null) {
				return ['_'];
			}
			$item = $permission[$key];
			if (is_string($item)) {
				return [$item];
			}
			return (array)$item;
		};
		$map = [];
        foreach ($this->permissions as $permission) {
            if (isset($permission['role'])) {
                $role = $permission['role'];
            } else {
                $role = '*';
            }
            $roles = (array)$role;
            foreach ($roles as $role) {
                $prefixes = $asArray($permission, 'prefix', '*');
                $plugins = $asArray($permission, 'plugin', '*');
                $controllers = $asArray($permission, 'controller', '*');
                foreach ($prefixes as $prefix) {
                    foreach ($plugins as $plugin) {
                        foreach ($controllers as $controller) {
                            $key = "$prefix|$plugin|$controller";
                            $map[$role][$key][] = $permission;
                        }
                    }
                }
            }
		}

		return $map;
    }

    /**
     * @param array $permissions permissions
     * @return void
     */
    public function setPermissions($permissions)
    {
        $this->permissions = $permissions;
    }

    /**
     * Match against permissions, return if matched
     * Permissions are processed based on the 'permissions' config values
     *
     * @param array|\ArrayAccess $user current user array
     * @param \Psr\Http\Message\ServerRequestInterface $request request
     * @return bool true if there is a match in permissions
     */
    public function checkPermissions($user, ServerRequestInterface $request)
    {
        $roleField = $this->getConfig('role_field');
        $defaultRole = $this->getConfig('default_role');
        $role = Hash::get($user, $roleField, $defaultRole);

        $keys = $this->permissionKeys($request);
        foreach ([$role, '*'] as $checkRole) {
            if (!array_key_exists($checkRole, $this->permissionsMap)) {
                continue;
            }
            foreach ($keys as $key) {
                if (!array_key_exists($key, $this->permissionsMap[$checkRole])) {
                    continue;
                }
                $permissions = $this->permissionsMap[$checkRole][$key];
                foreach ($permissions as $permission) {
                    $matchResult = $this->_matchPermission($permission, $user, $role, $request);
                    if ($matchResult !== null) {
                        if ($this->getConfig('log')) {
                            $this->log($matchResult->getReason(), LogLevel::DEBUG);
                        }

                        return $matchResult->isAllowed();
                    }
                }
            }
        }

        return false;
    }

    /**
     * Build list of permission keys to search.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request request
     * @return array list of key to search based on request.
     */
    protected function permissionKeys(ServerRequestInterface $request)
    {
        $params = $request->getAttribute('params');
        $permission = [
            'prefix' => $params['prefix'] ?? null,
            'plugin' => $params['plugin'] ?? null,
            'controller' => $params['controller'] ?? null,
        ];
        $keys = [];
		$getKeys = function ($permission, $key) {
			if ($permission[$key] == false || $permission[$key] == null) {
				return ['_', '*'];
			}
			$item = $permission[$key];
            if ($this->undasherize) {
                $item = Inflector::camelize((string)$item, '-');
            }
            return [$item, '*'];
		};
        $prefixes = $getKeys($permission, 'prefix');
        $plugins = $getKeys($permission, 'plugin');
        $controllers = $getKeys($permission, 'controller');
        foreach ($prefixes as $prefix) {
            foreach ($plugins as $plugin) {
                foreach ($controllers as $controller) {
                    $keys[] = "$prefix|$plugin|$controller";
                }
            }
        }

        return $keys;
    }

}
