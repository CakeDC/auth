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

namespace CakeDC\Auth\Test\TestCase\Middleware;

use CakeDC\Auth\Middleware\RbacMiddleware;
use CakeDC\Auth\Rbac\Rbac;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;

/**
 * Class RbacMiddlewareTest
 * @package TestCase\Middleware
 */
class RbacMiddlewareTest extends TestCase
{
    /**
     * @var RbacMiddleware
     */
    protected $rbacMiddleware;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        $this->rbacMiddleware = new RbacMiddleware();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        unset($this->rbacMiddleware);
    }

    /**
     * @test
     * @expectedException \Cake\Http\Exception\ForbiddenException
     */
    public function testInvokeForbidden()
    {
        $request = new ServerRequest();
        $response = new Response();
        $next = function () {
            return 'unreachable';
        };
        $rbacMiddleware = $this->rbacMiddleware;
        $rbacMiddleware->setConfig([
            'unauthorizedBehavior' => RbacMiddleware::UNAUTHORIZED_BEHAVIOR_THROW
        ]);
        $rbacMiddleware($request, $response, $next);
    }

    /**
     * @test
     */
    public function testInvokeRedirect()
    {
        $request = new ServerRequest();
        $response = new Response();
        $rbacMiddleware = $this->rbacMiddleware;
        Router::$initialized = true;
        Router::connect('/login', [
            'controller' => 'Users',
            'action' => 'login',
        ]);
        $next = function ($request, Response $response) {
            $this->assertSame(302, $response->getStatusCode());
            $this->assertSame('/login', $response->getHeaderLine('Location'));
        };
        $rbacMiddleware($request, $response, $next);
    }

    /**
     * @test
     */
    public function testInvokeAllowed()
    {
        $request = new ServerRequest();
        $userData = [
            'User' => [
                'id' => 1,
                'role' => 'user',
            ]
        ];
        $request = $request->withAttribute('identity', $userData);
        $response = new Response();
        $next = function () {
            return 'pass';
        };
        $rbac = $this->getMockBuilder(Rbac::class)
            ->setMethods(['checkPermissions'])
            ->getMock();
        $rbac->expects($this->once())
            ->method('checkPermissions')
            ->with($userData['User'], $request)
            ->willReturn(true);
        $rbacMiddleware = new RbacMiddleware($rbac, [
            'unauthorizedBehavior' => RbacMiddleware::UNAUTHORIZED_BEHAVIOR_THROW
        ]);
        $this->assertSame('pass', $rbacMiddleware($request, $response, $next));
    }
}
