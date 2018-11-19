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

use CakeDC\Auth\Auth\ApiKeyAuthenticate;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

class ApiKeyAuthenticateTest extends TestCase
{
    public $fixtures = [
        'plugin.CakeDC/Auth.users',
    ];

    /**
     * @var ApiKeyAuthenticate
     */
    public $apiKey;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        $request = new ServerRequest();
        $response = new Response();

        $controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->setMethods(null)
            ->setConstructorArgs([$request, $response])
            ->getMock();
        $registry = new ComponentRegistry($controller);
        $this->apiKey = new ApiKeyAuthenticate($registry, [
            'finder' => 'all',
            'require_ssl' => false]);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        unset($this->apiKey, $this->controller);
    }

    /**
     * test
     *
     * @return void
     */
    public function testAuthenticateHappy()
    {
        $request = new ServerRequest('/?api_key=xxx');
        $result = $this->apiKey->authenticate($request, new Response());
        $this->assertEquals('user-2', $result['username']);
    }

    /**
     * test
     *
     * @return void
     */
    public function testAuthenticateFail()
    {
        $request = new ServerRequest('/');
        $result = $this->apiKey->authenticate($request, new Response());
        $this->assertFalse($result);

        $request = new ServerRequest('/?api_key=none');
        $result = $this->apiKey->authenticate($request, new Response());
        $this->assertFalse($result);

        $request = new ServerRequest('/?api_key=');
        $result = $this->apiKey->authenticate($request, new Response());
        $this->assertFalse($result);
    }

    /**
     * test
     *
     * @expectedException \OutOfBoundsException
     * @expectedExceptionMessage Type wrong is not valid
     *
     */
    public function testAuthenticateWrongType()
    {
        $this->apiKey->setConfig('type', 'wrong');
        $request = new ServerRequest('/');
        $this->apiKey->authenticate($request, new Response());
    }

    /**
     * test
     *
     * @expectedException \Cake\Http\Exception\ForbiddenException
     * @expectedExceptionMessage SSL is required for ApiKey Authentication
     *
     */
    public function testAuthenticateRequireSSL()
    {
        $this->apiKey->setConfig('require_ssl', true);
        $request = new ServerRequest('/?api_key=test');
        $this->apiKey->authenticate($request, new Response());
    }

    /**
     * test
     *
     */
    public function testAuthenticateRequireSSLNoKey()
    {
        $this->apiKey->setConfig('require_ssl', true);
        $request = new ServerRequest('/');
        $this->assertFalse($this->apiKey->authenticate($request, new Response()));
    }

    /**
     * test
     *
     * @return void
     */
    public function testHeaderHappy()
    {
        $request = $this->getMockBuilder('\Cake\Http\ServerRequest')
            ->setMethods(['getHeader', 'getHeaderLine'])
            ->getMock();
        $request->expects($this->at(0))
            ->method('getHeader')
            ->with('api_key')
            ->will($this->returnValue(['xxx']));
        $request->expects($this->at(1))
            ->method('getHeaderLine')
            ->with('api_key')
            ->will($this->returnValue('xxx'));
        $this->apiKey->setConfig('type', 'header');
        $result = $this->apiKey->authenticate($request, new Response());
        $this->assertEquals('user-2', $result['username']);
    }

    /**
     * test
     *
     * @return void
     */
    public function testAuthenticateHeaderFail()
    {
        $request = $this->getMockBuilder('\Cake\Http\ServerRequest')
            ->setMethods(['getHeader', 'getHeaderLine'])
            ->getMock();
        $request->expects($this->at(0))
            ->method('getHeader')
            ->with('api_key')
            ->will($this->returnValue(['wrong']));
        $request->expects($this->at(1))
            ->method('getHeaderLine')
            ->with('api_key')
            ->will($this->returnValue('wrong'));
        $this->apiKey->setConfig('type', 'header');
        $result = $this->apiKey->authenticate($request, new Response());
        $this->assertFalse($result);
    }

    /**
     * test
     *
     * @return void
     */
    public function testAuthenticateHeaderNotPresent()
    {
        $request = $this->getMockBuilder('\Cake\Http\ServerRequest')
            ->setMethods(['getHeader'])
            ->getMock();
        $request->expects($this->once())
            ->method('getHeader')
            ->with('api_key')
            ->will($this->returnValue([]));
        $this->apiKey->setConfig('type', 'header');
        $result = $this->apiKey->authenticate($request, new Response());
        $this->assertFalse($result);
    }

    /**
     * test
     *
     * @return void
     */
    public function testAuthenticateFinderConfig()
    {
        $this->apiKey->setConfig('finder', 'undefinedInConfig');
        $request = new ServerRequest('/?api_key=xxx');
        try {
            $result = $this->apiKey->authenticate($request, new Response());
            $this->fail();
        } catch (\Exception $ex) {
            $this->assertEquals('Unknown finder method "undefinedInConfig"', $ex->getMessage());
        }
    }

    /**
     * test
     *
     * @return void
     */
    public function testAuthenticateFinderAuthConfig()
    {
        Configure::write('Auth.authenticate.all.finder', 'undefinedFinderInAuth');
        $this->apiKey->setConfig('finder', null);
        $request = new ServerRequest('/?api_key=xxx');
        try {
            $result = $this->apiKey->authenticate($request, new Response());
            $this->fail();
        } catch (\Exception $ex) {
            $this->assertEquals('Unknown finder method "undefinedFinderInAuth"', $ex->getMessage());
        }
    }

    /**
     * test
     *
     * @return void
     */
    public function testAuthenticateDefaultAllFinder()
    {
        Configure::write('Auth.authenticate.all.finder', null);
        $request = new ServerRequest('/?api_key=yyy');
        $result = $this->apiKey->authenticate($request, new Response());
        $this->assertEquals('user-1', $result['username']);
    }
}
