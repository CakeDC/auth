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

namespace CakeDC\Auth\Rbac\Permissions;

use Cake\Core\Configure;
use Cake\Core\InstanceConfigTrait;
use Cake\Log\LogTrait;
use Psr\Log\LogLevel;

/**
 * Class AbstractProvider, handles getting permission from different sources,
 * for example a config file
 */
abstract class AbstractProvider
{
    use InstanceConfigTrait;
    use LogTrait;

    /**
     * AbstractProvider constructor.
     * @param array $config config
     */
    public function __construct($config = [])
    {
        $this->setConfig($config);
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
     * Provide permissions array, for example
     * [
     *     [
                'role' => '*',
                'plugin' => null,
                'controller' => ['Pages'],
                'action' => ['display'],
            ],
     * ]
     *
     * @return array Array of permissions
     */
    abstract public function getPermissions();

    /**
     * @return array
     */
    public function getDefaultPermissions()
    {
        return $this->defaultPermissions;
    }

    /**
     * @param array $defaultPermissions default permissions
     * @return void
     */
    public function setDefaultPermissions($defaultPermissions)
    {
        $this->defaultPermissions = $defaultPermissions;
    }
}
