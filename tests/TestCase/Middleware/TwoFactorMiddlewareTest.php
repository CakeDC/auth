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

namespace CakeDC\Auth\Test\TestCase\Middleware;

use Authentication\Authenticator\Result;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Http\Runner;
use Cake\Http\ServerRequest;
use Cake\Http\ServerRequestFactory;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use CakeDC\Auth\Authentication\AuthenticationService;
use CakeDC\Auth\Middleware\TwoFactorMiddleware;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Class OneTimePasswordAuthenticatorMiddlewareTest
 *
 * @package TestCase\Middleware
 */
#[AllowMockObjectsWithoutExpectations]
class TwoFactorMiddlewareTest extends TestCase
{
    /**
     * @var TwoFactorMiddleware
     */
    protected $TwoFactorMiddleware;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp(): void
    {
        $this->TwoFactorMiddleware = new TwoFactorMiddleware();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown(): void
    {
        unset($this->TwoFactorMiddleware);
    }

    /**
     * Test middleware when dont't need to processed to one time password verify
     */
    public function testInvokeNotNeeded()
    {
        $request = new ServerRequest();
        $response = new Response();
        $response = $response->withStringBody(__METHOD__ . time());
        $handler = $this->getMockBuilder(Runner::class)
            ->onlyMethods(['handle'])
            ->getMock();
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn($response);

        $service = $this->getMockBuilder(AuthenticationService::class)->setConstructorArgs([
            [
                'authenticators' => [
                    'Authentication.Session' => [
                        'identifier' => 'Authentication.Password',
                    ],
                    'CakeDC/Auth.Form' => [
                        'identifier' => 'Authentication.Password',
                    ],
                ],
            ],
        ])->onlyMethods(['getResult'])->getMock();
        $result = new Result(['id' => 10, 'username' => 'johndoe'], Result::SUCCESS);
        $service->expects($this->any())
            ->method('getResult')
            ->willReturn($result);

        $request = $request->withAttribute('authentication', $service);
        $middleware = $this->TwoFactorMiddleware;
        $actual = $middleware->process($request, $handler);
        $this->assertSame($response, $actual);
    }

    /**
     * Test middleware when dont't need to processed to one time password verify
     */
    public function testInvokeNeedVerify()
    {
        $request = ServerRequestFactory::fromGlobals(
            ['REQUEST_URI' => '/login'],
            [],
            ['username' => 'user-1', 'password' => 'password', 'remember_me' => 1],
        );
        $handler = $this->getMockBuilder(Runner::class)
            ->onlyMethods(['handle'])
            ->getMock();
        $handler->expects($this->never())
            ->method('handle');
        Configure::write('OneTimePasswordAuthenticator.verifyAction', [
            'controller' => 'Users',
            'action' => 'verify',
        ]);
        Router::reload();
        $builder = Router::createRouteBuilder('/');
        $builder->connect('/verify', [
            'controller' => 'Users',
            'action' => 'verify',
        ]);
        $service = $this->getMockBuilder(AuthenticationService::class)->setConstructorArgs([
            [
                'authenticators' => [
                    'Authentication.Session' => [
                        'identifier' => 'Authentication.Password',
                    ],
                    'CakeDC/Auth.Form' => [
                        'identifier' => 'Authentication.Password',
                    ],
                ],
            ],
        ])->onlyMethods(['getResult'])->getMock();
        $result = new Result(null, AuthenticationService::NEED_TWO_FACTOR_VERIFY);
        $service->expects($this->any())
            ->method('getResult')
            ->willReturn($result);

        $request = $request->withAttribute('authentication', $service);
        $middleware = $this->TwoFactorMiddleware;
        $actual = $middleware->process($request, $handler);
        $this->assertInstanceOf(Response::class, $actual);
        $expected = [
            '/verify',
        ];
        $this->assertEquals($expected, $actual->getHeader('Location'));
        $this->assertSame(1, $request->getSession()->read('CookieAuth.remember_me'));
    }

    /**
     * @return array<string, array{0: string, 1: array, 2: string}>
     */
    public static function stringVerifyActionProvider(): array
    {
        return [
            'no query' => ['/verify', [], '/verify'],
            'query' => ['/verify', ['redirect' => '/posts/view/1'], '/verify?redirect=%2Fposts%2Fview%2F1'],
            'existing query' => ['/verify?step=2', ['a' => 'b c'], '/verify?step=2&a=b+c'],
            'full url' => ['https://example.com/verify', ['a' => '1'], 'https://example.com/verify?a=1'],
        ];
    }

    /**
     * Test middleware redirect when the verify action is a string url
     *
     * @param string $verifyAction configured verify action
     * @param array $query request query params
     * @param string $expected expected location
     * @return void
     */
    #[DataProvider('stringVerifyActionProvider')]
    public function testInvokeNeedVerifyStringUrl(string $verifyAction, array $query, string $expected): void
    {
        $request = ServerRequestFactory::fromGlobals(
            ['REQUEST_URI' => '/login'],
            $query,
            ['username' => 'user-1', 'password' => 'password'],
        );
        $handler = $this->getMockBuilder(Runner::class)
            ->onlyMethods(['handle'])
            ->getMock();
        $handler->expects($this->never())
            ->method('handle');
        Configure::write('OneTimePasswordAuthenticator.verifyAction', $verifyAction);
        $service = $this->getMockBuilder(AuthenticationService::class)->setConstructorArgs([
            [
                'authenticators' => [
                    'Authentication.Session' => [
                        'identifier' => 'Authentication.Password',
                    ],
                ],
            ],
        ])->onlyMethods(['getResult'])->getMock();
        $service->expects($this->any())
            ->method('getResult')
            ->willReturn(new Result(null, AuthenticationService::NEED_TWO_FACTOR_VERIFY));

        $actual = $this->TwoFactorMiddleware->process($request->withAttribute('authentication', $service), $handler);

        $this->assertSame([$expected], $actual->getHeader('Location'));
    }

    /**
     * @return array<string, array{0: bool}>
     */
    public static function noAuthenticationResultProvider(): array
    {
        return [
            'no authentication service' => [false],
            'service without result' => [true],
        ];
    }

    /**
     * Test the request is passed on when there is no authentication result
     *
     * @param bool $withService add an authentication service without result
     * @return void
     */
    #[DataProvider('noAuthenticationResultProvider')]
    public function testInvokeWithoutAuthenticationResult(bool $withService): void
    {
        $request = new ServerRequest();
        if ($withService) {
            $service = $this->getMockBuilder(AuthenticationService::class)
                ->onlyMethods(['getResult'])
                ->getMock();
            $service->method('getResult')->willReturn(null);
            $request = $request->withAttribute('authentication', $service);
        }
        $response = (new Response())->withStringBody('next');
        $handler = $this->getMockBuilder(Runner::class)
            ->onlyMethods(['handle'])
            ->getMock();
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn($response);

        $this->assertSame($response, $this->TwoFactorMiddleware->process($request, $handler));
    }
}
