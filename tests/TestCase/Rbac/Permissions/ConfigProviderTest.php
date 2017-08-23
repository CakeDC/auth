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

namespace CakeDC\Auth\Test\TestCase\Rbac\Permissions;

use CakeDC\Auth\Rbac\Permissions\ConfigProvider;
use Cake\TestSuite\TestCase;

/**
 * ConfigProviderTest
 * @property ConfigProvider configProvider
 */
class ConfigProviderTest extends TestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        $this->configProvider = new ConfigProvider();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        unset($this->configProvider);
    }

    /**
     * test
     *
     * @return void
     */
    public function testGetPermissionsNoAutoload()
    {
        $this->configProvider->setConfig('autoload_config', null);
        $this->configProvider->setDefaultPermissions([
            [
                'controller' => 'Posts',
                'action' => 'default',
            ]
        ]);
        $permissions = $this->configProvider->getPermissions();
        $this->assertSame($this->configProvider->getDefaultPermissions(), $permissions);
    }

    /**
     * test
     *
     * @return void
     */
    public function testGetPermissionsAutoloadMissingFileShouldReturnDefaultPermissions()
    {
        $this->configProvider->setConfig('autoload_config', 'missingFile');
        $permissions = $this->configProvider->getPermissions();
        $this->assertSame($this->configProvider->getDefaultPermissions(), $permissions);
    }

    /**
     * test
     *
     * @return void
     */
    public function testGetPermissionsAutoload()
    {
        $this->configProvider->setConfig('autoload_config', 'existing');
        $permissions = $this->configProvider->getPermissions();
        $this->assertSame([
            'controller' => 'Posts',
            'action' => 'display',
        ], $permissions);
    }
}
