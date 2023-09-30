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

namespace CakeDC\Auth\Test\TestCase\Social\Service;

use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\ServerRequest;
use Cake\Http\ServerRequestFactory;
use Cake\Http\Session;
use Cake\TestSuite\TestCase;
use CakeDC\Auth\Social\Mapper\LinkedInOpenIDConnect;
use CakeDC\Auth\Social\Service\OAuth2Service;
use CakeDC\Auth\Social\Service\OpenIDConnectService;
use CakeDC\Auth\Social\Service\ServiceInterface;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Laminas\Diactoros\Uri;
use League\OAuth2\Client\Provider\FacebookUser;
use League\OAuth2\Client\Provider\LinkedInResourceOwner;

class OpenIDConnectServiceTest extends TestCase
{
    /**
     * @var \CakeDC\Auth\Social\Service\OpenIDConnectService
     */
    public $Service;

    /**
     * @var \League\OAuth2\Client\Provider\Facebook
     */
    public $Provider;

    /**
     * @var \Cake\Http\ServerRequest
     */
    public $Request;

    /**
     * @var \Cake\Http\Client
     */
    public $Client;

    /**
     * Setup the test case, backup the static object values so they can be restored.
     * Specifically backs up the contents of Configure and paths in App if they have
     * not already been backed up.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->Client = $this->getMockBuilder(
            \Cake\Http\Client::class
        )->onlyMethods([
            'get',
        ])->getMock();

        $this->Provider = $this->getMockBuilder(
            \League\OAuth2\Client\Provider\LinkedIn::class
        )->setConstructorArgs([
            [
                'id' => '1',
                'firstName' => 'first',
                'lastName' => 'last',
            ],
            [],
        ])->setMethods([
            'getAccessToken', 'getState', 'getAuthorizationUrl', 'getResourceOwner',
        ])->getMock();

        $config = [
            'service' => OpenIDConnectService::class,
            'className' => $this->Provider,
            'mapper' => \CakeDC\Auth\Social\Mapper\LinkedInOpenIDConnect::class,
            'authParams' => ['scope' => ['public_profile', 'email', 'user_birthday', 'user_gender', 'user_link']],
            'options' => [
                'state' => '__TEST_STATE__',
            ],
            'collaborators' => [],
            'signature' => null,
            'mapFields' => [],
            'path' => [
                'plugin' => 'CakeDC/Users',
                'controller' => 'Users',
                'action' => 'socialLogin',
                'prefix' => null,
            ],
        ];

        $this->Service = $this->getMockBuilder(
            \CakeDC\Auth\Social\Service\OpenIDConnectService::class
        )->setConstructorArgs([
            $config,
        ])->onlyMethods([
            'getIdTokenKeys',
            'getHttpClient',
        ])->getMock();

        $this->Service->expects($this->any())
            ->method('getHttpClient')
            ->will($this->returnValue($this->Client));

        //new OpenIDConnectService($config);
        $this->Request = ServerRequestFactory::fromGlobals();
    }

    /**
     * teardown any static object changes and restore them.
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();

        unset($this->Provider, $this->Service, $this->Request);
    }

    /**
     * Test construct
     *
     * @return void
     */
    public function testConstruct()
    {
        $service = new OpenIDConnectService([
            'className' => \League\OAuth2\Client\Provider\LinkedIn::class,
            'mapper' => \CakeDC\Auth\Social\Mapper\LinkedInOpenIDConnect::class,
            'authParams' => ['scope' => ['public_profile', 'email', 'user_birthday', 'user_gender', 'user_link']],
            'options' => [],
            'collaborators' => [],
            'signature' => null,
            'mapFields' => [],
            'path' => [
                'plugin' => 'CakeDC/Users',
                'controller' => 'Users',
                'action' => 'socialLogin',
                'prefix' => null,
            ],
        ]);
        $this->assertInstanceOf(ServiceInterface::class, $service);
    }

    /**
     * Test GetUser InvalidRequest
     *
     * @return void
     */
    public function testGetUserInvalidRequest()
    {
        $this->Request = $this->Request->withQueryParams([
            'code' => 'ZPO9972j3092304230',
            'state' => '__TEST_STATE__',
        ]);

        $this->expectException(BadRequestException::class, 'Invalid OAuth2 state');

        $this->Service->getUser($this->Request);
    }

    /**
     * Test GetUser MisingIdToken
     *
     * @return void
     */
    public function testGetUserMisingIdToken()
    {
        $this->Request = $this->Request->withQueryParams([
            'code' => 'ZPO9972j3092304230',
            'state' => '__TEST_STATE__',
        ]);

        $this->Request->getSession()->write('oauth2state', '__TEST_STATE__');

        $token = new \League\OAuth2\Client\Token\AccessToken([
            'access_token' => 'test-token',
            'expires' => 1490988496,
        ]);

        $this->Provider->expects($this->once())
            ->method('getAccessToken')
            ->with(
                $this->equalTo('authorization_code'),
                $this->equalTo(['code' => 'ZPO9972j3092304230'])
            )
            ->will($this->returnValue($token));

        $this->expectException(BadRequestException::class, 'Missing id_token in response');

        $this->Service->getUser($this->Request);
    }

    /**
     * Test GetUser InvalidIdToken
     *
     * @return void
     */
    public function testGetUserInvalidIdToken()
    {
        $this->Request = $this->Request->withQueryParams([
            'code' => 'ZPO9972j3092304230',
            'state' => '__TEST_STATE__',
        ]);

        $this->Request->getSession()->write('oauth2state', '__TEST_STATE__');

        $token = new \League\OAuth2\Client\Token\AccessToken([
            'access_token' => 'test-token',
            'expires' => 1490988496,
            'id_token' => 'invalid-jwt',
        ]);

        $this->Provider->expects($this->once())
            ->method('getAccessToken')
            ->with(
                $this->equalTo('authorization_code'),
                $this->equalTo(['code' => 'ZPO9972j3092304230'])
            )
            ->will($this->returnValue($token));

        $this->expectException(BadRequestException::class, 'Invalid id token. Key may not be empty');

        $this->Service->getUser($this->Request);
    }

    /**
     * Test GetUser
     *
     * @return void
     */
    public function testGetUser()
    {
        $this->Request = $this->Request->withQueryParams([
            'code' => 'ZPO9972j3092304230',
            'state' => '__TEST_STATE__',
        ]);

        $this->Request->getSession()->write('oauth2state', '__TEST_STATE__');

        $key = 'example_key';
        $alg = 'HS256';
        $payload = [
            'iat' => 1490988496,
            'iss' => 'https://www.linkedin.com/',
        ];
        $kid = 'd929668a-bab1-4c69-9598-4373149723ff';
        $jwt = JWT::encode($payload, $key, $alg, $kid);

        $token = new \League\OAuth2\Client\Token\AccessToken([
            'access_token' => 'test-token',
            'expires' => 1490988496,
            'id_token' => $jwt,
        ]);

        $this->Provider->expects($this->once())
            ->method('getAccessToken')
            ->with(
                $this->equalTo('authorization_code'),
                $this->equalTo(['code' => 'ZPO9972j3092304230'])
            )
            ->will($this->returnValue($token));

        $jwksData = [
            $kid => new Key($key, $alg),
        ];

        $this->Service->expects($this->once())
            ->method('getIdTokenKeys')
            ->will($this->returnValue($jwksData));

        $actual = $this->Service->getUser($this->Request);

        $expected = [
            'token' => $token,
            'iat' => 1490988496,
            'iss' => 'https://www.linkedin.com/',
        ];

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test discover
     *
     * @return void
     */
    public function testDiscover()
    {
        $arrayTest = ['test' => 'test'];
        $response = new \Cake\Http\Client\Response([], json_encode($arrayTest));

        $this->Client->expects($this->once())
            ->method('get')
            ->will($this->returnValue($response));

        $actual = $this->Service->discover();

        $this->assertEquals($arrayTest, $actual);
    }
}
