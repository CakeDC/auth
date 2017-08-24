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

namespace CakeDC\Auth\Test\TestCase\Auth;

use CakeDC\Auth\Auth\SimpleRbacAuthorize;
use Cake\Controller\ComponentRegistry;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

class SimpleRbacAuthorizeTest extends TestCase
{

    /**
     * @var SimpleRbacAuthorize
     */
    protected $simpleRbacAuthorize;

    protected $defaultPermissions = [
        //admin role allowed to use CakeDC\Auth\Auth plugin actions
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
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        $request = new ServerRequest();
        $response = new Response();

        $this->controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->setMethods(null)
            ->setConstructorArgs([$request, $response])
            ->getMock();
        $this->registry = new ComponentRegistry($this->controller);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        unset($this->simpleRbacAuthorize, $this->controller);
    }

    /**
     * test
     */
    public function testConstruct()
    {
        //don't autoload config
        $this->simpleRbacAuthorize = new SimpleRbacAuthorize($this->registry, ['autoload_config' => false]);
        $this->assertEmpty($this->simpleRbacAuthorize->getConfig('permissions'));
    }

    /**
     * @test
     */
    public function testAuthorize()
    {
        $user = [
            'id' => 1,
            'role' => 'test',
        ];

        $this->simpleRbacAuthorize = new SimpleRbacAuthorize($this->registry, [
            'autoload_config' => false,
            'permissions' => [
                [
                    'plugin' => '*',
                    'controller' => '*',
                    'action' => '*',
                    'role' => 'test',
                ]
            ]
        ]);

        $request = new ServerRequest();
        $request = $request->withAttribute('params', [
            'plugin' => 'Tests',
            'controller' => 'Tests',
            'action' => 'one'
        ]);

        $this->assertTrue($this->simpleRbacAuthorize->authorize($user, $request));
    }
}
