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

use CakeDC\Auth\Auth\RememberMeAuthenticate;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

class RememberMeAuthenticateTest extends TestCase
{
    public $fixtures = [
        'plugin.CakeDC/Auth.users',
    ];

    /**
     * @var RememberMeAuthenticate
     */
    protected $_rememberMe;

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
        $registry = new ComponentRegistry($this->controller);
        $this->rememberMe = new RememberMeAuthenticate($registry);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        unset($this->rememberMe, $this->controller);
    }

    /**
     * test
     *
     * @return void
     */
    public function testAuthenticateHappy()
    {
        $request = new ServerRequest('/');
        $request = $request->withEnv('HTTP_USER_AGENT', 'user-agent');
        $mockCookie = $this->getMockBuilder('Cake\Controller\Component\CookieComponent')
                ->disableOriginalConstructor()
                ->setMethods(['check', 'read'])
                ->getMock();
        $mockCookie
                ->expects($this->once())
                ->method('read')
                ->with('remember_me')
                ->will($this->returnValue([
                    'id' => '00000000-0000-0000-0000-000000000001',
                    'user_agent' => 'user-agent'
                ]));
        $registry = new ComponentRegistry($this->controller);
        $this->controller->Cookie = $mockCookie;
        $this->rememberMe = new RememberMeAuthenticate($registry);
        $result = $this->rememberMe->authenticate($request, new Response());
        $this->assertEquals('user-1', $result['username']);
    }

    /**
     * test
     *
     * @return void
     */
    public function testAuthenticateDisabledRememberMe()
    {
        Configure::write('Users.RememberMe.active', false);
        $request = new ServerRequest('/');
        $request = $request->withEnv('HTTP_USER_AGENT', 'user-agent');
        $mockCookie = $this->getMockBuilder('Cake\Controller\Component\CookieComponent')
                ->disableOriginalConstructor()
                ->setMethods(['check', 'read'])
                ->getMock();
        $mockCookie
                ->expects($this->never())
                ->method('read');

        $registry = new ComponentRegistry($this->controller);
        $this->controller->Cookie = $mockCookie;
        $this->rememberMe = new RememberMeAuthenticate($registry);
        $result = $this->rememberMe->authenticate($request, new Response());
        $this->assertFalse($result);
        Configure::delete('Users.RememberMe.active');
    }

    /**
     * test
     *
     * @return void
     */
    public function testAuthenticateBadUser()
    {
        $request = new ServerRequest('/');
        $request = $request->withEnv('HTTP_USER_AGENT', 'user-agent');
        $mockCookie = $this->getMockBuilder('Cake\Controller\Component\CookieComponent')
                ->disableOriginalConstructor()
                ->setMethods(['check', 'read'])
                ->getMock();
        $mockCookie
                ->expects($this->once())
                ->method('read')
                ->with('remember_me')
                ->will($this->returnValue([
                    //bad-user
                    'id' => '00000000-0000-0000-0000-000000000000',
                    'user_agent' => 'user-agent'
                ]));
        $registry = new ComponentRegistry($this->controller);
        $this->controller->Cookie = $mockCookie;
        $this->rememberMe = new RememberMeAuthenticate($registry);
        $result = $this->rememberMe->authenticate($request, new Response());
        $this->assertFalse($result);
    }

    /**
     * test
     *
     * @return void
     */
    public function testAuthenticateBadAgent()
    {
        $request = new ServerRequest('/');
        $request = $request->withEnv('HTTP_USER_AGENT', 'user-agent');
        $mockCookie = $this->getMockBuilder('Cake\Controller\Component\CookieComponent')
                ->disableOriginalConstructor()
                ->setMethods(['check', 'read'])
                ->getMock();
        $mockCookie
                ->expects($this->once())
                ->method('read')
                ->with('remember_me')
                ->will($this->returnValue([
                    'id' => '00000000-0000-0000-0000-000000000001',
                    'user_agent' => 'bad-agent'
                ]));
        $registry = new ComponentRegistry($this->controller);
        $this->controller->Cookie = $mockCookie;
        $this->rememberMe = new RememberMeAuthenticate($registry);
        $result = $this->rememberMe->authenticate($request, new Response());
        $this->assertFalse($result);
    }

    /**
     * test
     *
     * @return void
     */
    public function testAuthenticateNoCookie()
    {
        $request = new ServerRequest('/');
        $request = $request->withEnv('HTTP_USER_AGENT', 'user-agent');
        $mockCookie = $this->getMockBuilder('Cake\Controller\Component\CookieComponent')
                ->disableOriginalConstructor()
                ->setMethods(['check', 'read'])
                ->getMock();
        $mockCookie
                ->expects($this->once())
                ->method('read')
                ->with('remember_me')
                ->will($this->returnValue(null));

        $registry = new ComponentRegistry($this->controller);
        $this->controller->Cookie = $mockCookie;
        $this->rememberMe = new RememberMeAuthenticate($registry);
        $result = $this->rememberMe->authenticate($request, new Response());
        $this->assertFalse($result);
    }

    /**
     * test
     *
     * @return void
     */
    public function testAuthenticateFormTimeOut()
    {
        $request = new ServerRequest('/');
        $request = $request->withEnv('HTTP_USER_AGENT', 'user-agent');

        $mockCookie = $this->getMockBuilder('Cake\Controller\Component\CookieComponent')
            ->disableOriginalConstructor()
            ->setMethods(['check', 'read'])
            ->getMock();
        $mockCookie
            ->expects($this->once())
            ->method('read')
            ->with('remember_me')
            ->will($this->returnValue([
                'id' => '00000000-0000-0000-0000-000000000002',
                'user_agent' => 'user-agent'
            ]));
        $this->controller->Cookie = $mockCookie;

        $mockAuthenticate = $this->getMockBuilder('CakeDC\Auth\Auth\RememberMeAuthenticate')
            ->disableOriginalConstructor()
            ->setMethods(['getUser'])
            ->getMock();
        $mockAuthenticate
            ->expects($this->never())
            ->method('getUser');
        $this->rememberMe = $mockAuthenticate;

        $registry = new ComponentRegistry($this->controller);
        $this->rememberMe = new RememberMeAuthenticate($registry);
        $result = $this->rememberMe->getUser($request);
        $this->assertEquals('user-2', $result['username']);
    }
}
