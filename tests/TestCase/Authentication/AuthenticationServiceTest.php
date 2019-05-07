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

use Authentication\Authenticator\Result;
use CakeDC\Auth\Authentication\AuthenticationService;
use CakeDC\Auth\Authentication\Failure;
use CakeDC\Auth\Authenticator\FormAuthenticator;
use Cake\Core\Configure;
use Cake\Http\Client\Response;
use Cake\Http\ServerRequestFactory;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuthenticationServiceTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.CakeDC/Auth.Users',
        'plugin.CakeDC/Auth.SocialAccounts'
    ];

    /**
     * testAuthenticate
     *
     * @return void
     */
    public function testAuthenticateEmptyAuthenticators()
    {
        $request = ServerRequestFactory::fromGlobals(
            ['REQUEST_URI' => '/testpath'],
            [],
            ['username' => 'user-not-found', 'password' => 'password']
        );
        $response = new Response();

        $service = new AuthenticationService([
            'identifiers' => [
                'Authentication.Password'
            ],
            'authenticators' => []
        ]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No authenticators loaded. You need to load at least one authenticator.');
        $service->authenticate($request, $response);
    }

    /**
     * testAuthenticate
     *
     * @return void
     */
    public function testAuthenticateFail()
    {
        $Table = TableRegistry::getTableLocator()->get('CakeDC/Auth.Users');
        $Table->setEntityClass(\CakeDC\Auth\Test\App\Model\Entity\User::class);
        $entity = $Table->get('00000000-0000-0000-0000-000000000001');
        $entity->password = 'password';
        $this->assertTrue((bool)$Table->save($entity));
        $request = ServerRequestFactory::fromGlobals(
            ['REQUEST_URI' => '/testpath'],
            [],
            ['username' => 'user-not-found', 'password' => 'password']
        );
        $response = new Response();

        $service = new AuthenticationService([
            'identifiers' => [
                'Authentication.Password'
            ],
            'authenticators' => [
                'Authentication.Session',
                'CakeDC/Auth.Form'
            ]
        ]);

        $result = $service->authenticate($request, $response);
        $this->assertInstanceOf(Result::class, $result['result']);
        $this->assertInstanceOf(ServerRequestInterface::class, $result['request']);
        $this->assertInstanceOf(ResponseInterface::class, $result['response']);
        $this->assertFalse($result['result']->isValid());
        $result = $service->getAuthenticationProvider();
        $this->assertNull($result);
        $this->assertNull(
            $request->getAttribute('session')->read('Auth')
        );
        $this->assertEmpty($response->getHeaderLine('Location'));
        $this->assertNull($response->getStatusCode());

        $sessionFailure = new Failure(
            $service->authenticators()->get('Session'),
            new Result(null, Result::FAILURE_IDENTITY_NOT_FOUND)
        );
        $formFailure = new Failure(
            $service->authenticators()->get('Form'),
            new Result(null, Result::FAILURE_IDENTITY_NOT_FOUND, [
                'Password' => []
            ])
        );
        $expected = [$sessionFailure, $formFailure];
        $actual = $service->getFailures();
        $this->assertEquals($expected, $actual);
    }

    /**
     * testAuthenticate
     *
     * @return void
     */
    public function testAuthenticate()
    {
        Configure::write('OneTimePasswordAuthenticator.login', false);
        Configure::write('U2f.enabled', false);
        $Table = TableRegistry::getTableLocator()->get('CakeDC/Auth.Users');
        $Table->setEntityClass(\CakeDC\Auth\Test\App\Model\Entity\User::class);
        $entity = $Table->get('00000000-0000-0000-0000-000000000001');
        $entity->password = 'password';
        $this->assertTrue((bool)$Table->save($entity));
        $request = ServerRequestFactory::fromGlobals(
            ['REQUEST_URI' => '/testpath'],
            [],
            ['username' => 'user-1', 'password' => 'password']
        );
        $response = new Response();

        $service = new AuthenticationService([
            'identifiers' => [
                'Authentication.Password'
            ],
            'authenticators' => [
                'Authentication.Session',
                'CakeDC/Auth.Form'
            ]
        ]);

        $result = $service->authenticate($request, $response);
        $this->assertInstanceOf(Result::class, $result['result']);
        $this->assertInstanceOf(ServerRequestInterface::class, $result['request']);
        $this->assertInstanceOf(ResponseInterface::class, $result['response']);
        $this->assertTrue($result['result']->isValid());

        $result = $service->getAuthenticationProvider();
        $this->assertInstanceOf(FormAuthenticator::class, $result);

        $this->assertEquals(
            'user-1',
            $request->getAttribute('session')->read('Auth.username')
        );
        $this->assertEmpty($response->getHeaderLine('Location'));
        $this->assertNull($response->getStatusCode());

        $sessionFailure = new Failure(
            $service->authenticators()->get('Session'),
            new Result(null, Result::FAILURE_IDENTITY_NOT_FOUND)
        );
        $expected = [$sessionFailure];
        $actual = $service->getFailures();
        $this->assertEquals($expected, $actual);
    }

    /**
     * testAuthenticate
     *
     * @return void
     */
    public function testAuthenticateShouldDoGoogleVerifyEnabled()
    {
        Configure::write('OneTimePasswordAuthenticator.login', true);
        Configure::write('U2f.enabled', false);
        $Table = TableRegistry::getTableLocator()->get('CakeDC/Auth.Users');
        $Table->setEntityClass(\CakeDC\Auth\Test\App\Model\Entity\User::class);
        $entity = $Table->get('00000000-0000-0000-0000-000000000001');
        $entity->password = 'password';
        $this->assertTrue((bool)$Table->save($entity));
        $request = ServerRequestFactory::fromGlobals(
            ['REQUEST_URI' => '/testpath'],
            [],
            ['username' => 'user-1', 'password' => 'password']
        );
        $response = new Response();

        $service = new AuthenticationService([
            'identifiers' => [
                'Authentication.Password' => []
            ],
            'authenticators' => [
                'Authentication.Session' => [
                    'skipTwoFactorVerify' => true,
                ],
                'CakeDC/Auth.Form' => [
                    'skipTwoFactorVerify' => false,
                ]
            ]
        ]);

        $result = $service->authenticate($request, $response);
        $this->assertInstanceOf(Result::class, $result['result']);
        $this->assertInstanceOf(ServerRequestInterface::class, $result['request']);
        $this->assertInstanceOf(ResponseInterface::class, $result['response']);
        $this->assertFalse($result['result']->isValid());
        $this->assertEquals(AuthenticationService::NEED_TWO_FACTOR_VERIFY, $result['result']->getStatus());
        $this->assertNull($request->getAttribute('session')->read('Auth.username'));
        $this->assertEquals(
            'user-1',
            $request->getAttribute('session')->read('temporarySession.username')
        );
        $sessionFailure = new Failure(
            $service->authenticators()->get('Session'),
            new Result(null, Result::FAILURE_IDENTITY_NOT_FOUND)
        );
        $expected = [$sessionFailure];
        $actual = $service->getFailures();
        $this->assertEquals($expected, $actual);
    }

    /**
     * testAuthenticate
     *
     * @return void
     */
    public function testAuthenticateShouldDoGoogleVerifyDisabled()
    {
        Configure::write('U2f.enabled', false);
        Configure::write('OneTimePasswordAuthenticator.login', false);
        $Table = TableRegistry::getTableLocator()->get('CakeDC/Auth.Users');
        $Table->setEntityClass(\CakeDC\Auth\Test\App\Model\Entity\User::class);
        $entity = $Table->get('00000000-0000-0000-0000-000000000001');
        $entity->password = 'password';
        $this->assertTrue((bool)$Table->save($entity));
        $request = ServerRequestFactory::fromGlobals(
            ['REQUEST_URI' => '/testpath'],
            [],
            ['username' => 'user-1', 'password' => 'password']
        );
        $response = new Response();

        $service = new AuthenticationService([
            'identifiers' => [
                'Authentication.Password' => []
            ],
            'authenticators' => [
                'Authentication.Session' => [
                    'skipTwoFactorVerify' => true,
                ],
                'CakeDC/Auth.Form' => [
                    'skipTwoFactorVerify' => false,
                ]
            ]
        ]);

        $result = $service->authenticate($request, $response);
        $this->assertInstanceOf(Result::class, $result['result']);
        $this->assertInstanceOf(ServerRequestInterface::class, $result['request']);
        $this->assertInstanceOf(ResponseInterface::class, $result['response']);

        $this->assertTrue($result['result']->isValid());

        $result = $service->getAuthenticationProvider();
        $this->assertInstanceOf(FormAuthenticator::class, $result);

        $this->assertEquals(
            'user-1',
            $request->getAttribute('session')->read('Auth.username')
        );
        $this->assertEmpty($response->getHeaderLine('Location'));
        $this->assertNull($response->getStatusCode());
        $sessionFailure = new Failure(
            $service->authenticators()->get('Session'),
            new Result(null, Result::FAILURE_IDENTITY_NOT_FOUND)
        );
        $expected = [$sessionFailure];
        $actual = $service->getFailures();
        $this->assertEquals($expected, $actual);
    }

    /**
     * testAuthenticate
     *
     * @return void
     */
    public function testAuthenticateShouldDoU2fEnabled()
    {
        Configure::write('U2f.enabled', true);
        $Table = TableRegistry::getTableLocator()->get('CakeDC/Auth.Users');
        $Table->setEntityClass(\CakeDC\Auth\Test\App\Model\Entity\User::class);
        $entity = $Table->get('00000000-0000-0000-0000-000000000001');
        $entity->password = 'password';
        $this->assertTrue((bool)$Table->save($entity));
        $request = ServerRequestFactory::fromGlobals(
            ['REQUEST_URI' => '/testpath'],
            [],
            ['username' => 'user-1', 'password' => 'password']
        );
        $response = new Response();

        $service = new AuthenticationService([
            'identifiers' => [
                'Authentication.Password' => []
            ],
            'authenticators' => [
                'Authentication.Session' => [
                    'skipTwoFactorVerify' => true,
                ],
                'CakeDC/Auth.Form' => [
                    'skipTwoFactorVerify' => false,
                ]
            ]
        ]);

        $result = $service->authenticate($request, $response);
        $this->assertInstanceOf(Result::class, $result['result']);
        $this->assertInstanceOf(ServerRequestInterface::class, $result['request']);
        $this->assertInstanceOf(ResponseInterface::class, $result['response']);
        $this->assertFalse($result['result']->isValid());
        $this->assertEquals(AuthenticationService::NEED_U2F_VERIFY, $result['result']->getStatus());
        $this->assertNull($request->getAttribute('session')->read('Auth.username'));
        $this->assertEquals(
            'user-1',
            $request->getAttribute('session')->read('U2f.User.username')
        );
        $sessionFailure = new Failure(
            $service->authenticators()->get('Session'),
            new Result(null, Result::FAILURE_IDENTITY_NOT_FOUND)
        );
        $expected = [$sessionFailure];
        $actual = $service->getFailures();
        $this->assertEquals($expected, $actual);
    }

    /**
     * testAuthenticate
     *
     * @return void
     */
    public function testAuthenticateShouldDoU2fDisabled()
    {
        Configure::write('U2f.enabled', false);
        $Table = TableRegistry::getTableLocator()->get('CakeDC/Auth.Users');
        $Table->setEntityClass(\CakeDC\Auth\Test\App\Model\Entity\User::class);
        $entity = $Table->get('00000000-0000-0000-0000-000000000001');
        $entity->password = 'password';
        $this->assertTrue((bool)$Table->save($entity));
        $request = ServerRequestFactory::fromGlobals(
            ['REQUEST_URI' => '/testpath'],
            [],
            ['username' => 'user-1', 'password' => 'password']
        );
        $response = new Response();

        $service = new AuthenticationService([
            'identifiers' => [
                'Authentication.Password' => []
            ],
            'authenticators' => [
                'Authentication.Session' => [
                    'skipTwoFactorVerify' => true,
                ],
                'CakeDC/Auth.Form' => [
                    'skipTwoFactorVerify' => false,
                ]
            ]
        ]);

        $result = $service->authenticate($request, $response);
        $this->assertInstanceOf(Result::class, $result['result']);
        $this->assertInstanceOf(ServerRequestInterface::class, $result['request']);
        $this->assertInstanceOf(ResponseInterface::class, $result['response']);

        $this->assertTrue($result['result']->isValid());

        $result = $service->getAuthenticationProvider();
        $this->assertInstanceOf(FormAuthenticator::class, $result);

        $this->assertEquals(
            'user-1',
            $request->getAttribute('session')->read('Auth.username')
        );
        $this->assertEmpty($response->getHeaderLine('Location'));
        $this->assertNull($response->getStatusCode());
        $sessionFailure = new Failure(
            $service->authenticators()->get('Session'),
            new Result(null, Result::FAILURE_IDENTITY_NOT_FOUND)
        );
        $expected = [$sessionFailure];
        $actual = $service->getFailures();
        $this->assertEquals($expected, $actual);
    }
}
