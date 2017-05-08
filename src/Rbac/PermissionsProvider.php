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
use Cake\Core\Configure;
use Cake\Log\LogTrait;
use Psr\Log\LogLevel;

/**
 * Class PermissionsProvider, handles permission loading from configuration file
 *
 * @package Rbac
 * @todo create strategy for permission loading
 */
class PermissionsProvider
{
    use LogTrait;

    /**
     * Load permissions array
     *
     * @param $autoload
     * @return array
     */
    public function loadPermissions($autoload)
    {
        if ($autoload) {
            return $this->_loadPermissions($autoload);
        }

        return [];
    }

    /**
     * Default permissions to be loaded if no provided permissions
     *
     * @var array
     */
    protected $defaultPermissions = [
        //admin role allowed to all actions
        [
            'role' => 'admin',
            'plugin' => '*',
            'controller' => '*',
            'action' => '*',
        ],
        //specific actions allowed for the user role in Users plugin
        [
            'role' => 'user',
            'plugin' => 'CakeDC/Users',
            'controller' => 'Users',
            'action' => ['profile', 'logout'],
        ],
        //all roles allowed to Pages/display
        [
            'role' => '*',
            'plugin' => null,
            'controller' => ['Pages'],
            'action' => ['display'],
        ],
    ];

    /**
     * Load config and retrieve permissions
     * If the configuration file does not exist, or the permissions key not present, return defaultPermissions
     * To be mocked
     *
     * @param string $key name of the configuration file to read permissions from
     * @return array permissions
     */
    protected function _loadPermissions($key)
    {
        try {
            Configure::load($key, 'default');
            $permissions = Configure::read('CakeDC/Auth.permissions');
        } catch (\Exception $ex) {
            $msg = sprintf('Missing configuration file: "config/%s.php". Using default permissions', $key);
            $this->log($msg, LogLevel::WARNING);
        }

        if (empty($permissions)) {
            return $this->defaultPermissions;
        }

        return $permissions;
    }

}