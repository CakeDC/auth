<?php
/**
 * Copyright 2010 - 2019, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010 - 2018, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Auth\Test\TestCase\Middleware;

use Authentication\Authenticator\Result;
use CakeDC\Auth\Authentication\AuthenticationService;
use CakeDC\Auth\Middleware\OneTimePasswordAuthenticatorMiddleware;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Http\ServerRequestFactory;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;

/**
 * Class OneTimePasswordAuthenticatorMiddlewareTest
 * @package TestCase\Middleware
 */
class OneTimePasswordAuthenticatorMiddlewareTest extends TestCase
{
    /**
     * @var OneTimePasswordAuthenticatorMiddleware
     */
    protected $OneTimePasswordAuthenticatorMiddleware;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        $this->OneTimePasswordAuthenticatorMiddleware = new OneTimePasswordAuthenticatorMiddleware();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        unset($this->OneTimePasswordAuthenticatorMiddleware);
    }

    /**
     * @test middleware when dont't need to processed to one time password verify
     */
    public function testInvokeNotNeeded()
    {
        $request = new ServerRequest();
        $response = new Response();

        $service = $this->getMockBuilder(AuthenticationService::class)->setConstructorArgs([
            [
                'identifiers' => [
                    'Authentication.Password'
                ],
                'authenticators' => [
                    'Authentication.Session',
                    'CakeDC/Auth.Form'
                ]
            ]
        ])->setMethods(['getResult'])->getMock();
        $result = new Result(['id' => 10, 'username' => 'johndoe'], Result::SUCCESS);
        $service->expects($this->any())
            ->method('getResult')
            ->will($this->returnValue($result));

        $request = $request->withAttribute('authentication', $service);
        $middleware = $this->OneTimePasswordAuthenticatorMiddleware;
        $self = $this;
        $result = false;
        $next = function ($aRequest, $aResponse) use ($request, $response, $self, &$result) {
            $self->assertSame($request, $aRequest);
            $self->assertSame($response, $aResponse);
            $result = true;
        };
        $middleware($request, $response, $next);
        $this->assertTrue($result);
    }

    /**
     * @test middleware when dont't need to processed to one time password verify
     */
    public function testInvokeNeedVerify()
    {
        $request = ServerRequestFactory::fromGlobals(
            ['REQUEST_URI' => '/login'],
            [],
            ['username' => 'user-1', 'password' => 'password', 'remember_me' => 1]
        );
        $response = new Response();
        Configure::write('OneTimePasswordAuthenticator.verifyAction', [
            'controller' => 'Users',
            'action' => 'verify',
        ]);
        Router::$initialized = true;
        Router::connect('/verify', [
            'controller' => 'Users',
            'action' => 'verify',
        ]);
        $service = $this->getMockBuilder(AuthenticationService::class)->setConstructorArgs([
            [
                'identifiers' => [
                    'Authentication.Password'
                ],
                'authenticators' => [
                    'Authentication.Session',
                    'CakeDC/Auth.Form'
                ]
            ]
        ])->setMethods(['getResult'])->getMock();
        $result = new Result(null, AuthenticationService::NEED_TWO_FACTOR_VERIFY);
        $service->expects($this->any())
            ->method('getResult')
            ->will($this->returnValue($result));

        $request = $request->withAttribute('authentication', $service);
        $middleware = $this->OneTimePasswordAuthenticatorMiddleware;
        $next = function () {
            throw new \UnexpectedValueException("Should not be called");
        };
        $actual = $middleware($request, $response, $next);
        $this->assertInstanceOf(Response::class, $actual);
        $expected = [
            '/verify'
        ];
        $this->assertEquals($expected, $actual->getHeader('Location'));
        $this->assertSame(1, $request->getSession()->read('CookieAuth.remember_me'));
    }
}
