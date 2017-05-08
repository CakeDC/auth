<?php
/**
 * Copyright 2010 - 2017, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010 - 2017, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Auth\Rbac;

use Cake\Http\ServerRequest;
use Cake\Log\LogTrait;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use CakeDC\Auth\Rbac\Rules\Rule;
use Psr\Log\LogLevel;

/**
 * Class Rbac, determine if a request matches any of the given rbac rules
 *
 * @package Rbac
 */
class Rbac
{
    use LogTrait;

    /**
     * @var array rules array
     */
    protected $permissions;

    /**
     * Rbac constructor.
     * @param string $autoload Config key to load permissions from file
     * @param array $permissions Permissions array, will not load permissions if provided
     */
    public function __construct($autoload = 'permissions', $permissions = null)
    {
        if ($permissions) {
            $this->permissions = $permissions;
        } else {
            $permissionsProvider = new PermissionsProvider();
            $this->permissions = $permissionsProvider->loadPermissions($autoload);
        }
    }

    /**
     * @return array
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * @param array $permissions
     */
    public function setPermissions($permissions)
    {
        $this->permissions = $permissions;
    }

    /**
     * Match against permissions, return if matched
     * Permissions are processed based on the 'permissions' config values
     *
     * @param array $user current user array
     * @param string $role effective role for the current user
     * @param \Cake\Http\ServerRequest $request request
     * @return bool true if there is a match in permissions
     */
    public function checkPermissions(array $user, $role, ServerRequest $request)
    {
        foreach ($this->permissions as $permission) {
            $allowed = $this->_matchPermission($permission, $user, $role, $request);
            if ($allowed !== null) {
                return $allowed;
            }
        }

        return false;
    }

    /**
     * Match the rule for current permission
     *
     * @param array $permission The permission configuration
     * @param array $user Current user data
     * @param string $role Effective user's role
     * @param \Cake\Http\ServerRequest $request Current request
     *
     * @return null|bool Null if permission is discarded, boolean if a final result is produced
     */
    protected function _matchPermission(array $permission, array $user, $role, ServerRequest $request)
    {
        $issetController = isset($permission['controller']) || isset($permission['*controller']);
        $issetAction = isset($permission['action']) || isset($permission['*action']);
        $issetUser = isset($permission['user']) || isset($permission['*user']);

        if (!$issetController || !$issetAction) {
            $this->log(
                "Cannot evaluate permission when 'controller' and/or 'action' keys are absent",
                LogLevel::DEBUG
            );

            return false;
        }
        if ($issetUser) {
            $this->log(
                "Permission key 'user' is illegal, cannot evaluate the permission",
                LogLevel::DEBUG
            );

            return false;
        }

        $permission += ['allowed' => true];
        $userArr = ['user' => $user];
        $reserved = [
            'prefix' => $request->getParam('prefix'),
            'plugin' => $request->getParam('plugin'),
            'extension' => $request->getParam('_ext'),
            'controller' => $request->getParam('controller'),
            'action' => $request->getParam('action'),
            'role' => $role
        ];

        foreach ($permission as $key => $value) {
            $inverse = $this->_startsWith($key, '*');
            if ($inverse) {
                $key = ltrim($key, '*');
            }

            if (is_callable($value)) {
                $return = (bool)call_user_func($value, $user, $role, $request);
            } elseif ($value instanceof Rule) {
                $return = (bool)$value->allowed($user, $role, $request);
            } elseif ($key === 'allowed') {
                $return = (bool)$value;
            } elseif (array_key_exists($key, $reserved)) {
                $return = $this->_matchOrAsterisk($value, $reserved[$key], true);
            } else {
                if (!$this->_startsWith($key, 'user.')) {
                    $key = 'user.' . $key;
                }

                $return = $this->_matchOrAsterisk($value, Hash::get($userArr, $key));
            }

            if ($inverse) {
                $return = !$return;
            }
            if ($key === 'allowed') {
                return $return;
            }
            if (!$return) {
                break;
            }
        }

        return null;
    }

    /**
     * Check if rule matched or '*' present in rule matching anything
     *
     * @param string|array $possibleValues Values that are accepted (from permission config)
     * @param string|mixed|null $value Value to check with. We'll check the 'dasherized' value too
     * @param bool $allowEmpty If true and $value is null, the rule will pass
     *
     * @return bool
     */
    protected function _matchOrAsterisk($possibleValues, $value, $allowEmpty = false)
    {
        $possibleArray = (array)$possibleValues;

        if ($allowEmpty && empty($possibleArray) && $value === null) {
            return true;
        }

        if ($possibleValues === '*' ||
            in_array($value, $possibleArray) ||
            in_array(Inflector::camelize($value, '-'), $possibleArray)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Checks if $haystack begins with $needle
     *
     * @see http://stackoverflow.com/a/7168986/2588539
     *
     * @param string $haystack The whole string
     * @param string $needle The beginning to check
     *
     * @return bool
     */
    protected function _startsWith($haystack, $needle)
    {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }
}
