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

use Cake\Cache\Cache;
use Cake\Http\Client;
use Cake\Http\Client\Response;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\ServerRequestFactory;
use Cake\TestSuite\TestCase;
use CakeDC\Auth\Social\Mapper\LinkedInOpenIDConnect;
use CakeDC\Auth\Social\Service\OpenIDConnectService;
use CakeDC\Auth\Social\Service\ServiceInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use League\OAuth2\Client\Provider\LinkedIn;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;

#[AllowMockObjectsWithoutExpectations]
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
            Client::class,
        )->onlyMethods([
            'get',
        ])->getMock();

        $this->Provider = $this->getMockBuilder(
            LinkedIn::class,
        )->setConstructorArgs([
            [
                'id' => '1',
                'firstName' => 'first',
                'lastName' => 'last',
            ],
            [],
        ])->onlyMethods([
            'getAccessToken', 'getState', 'getAuthorizationUrl', 'getResourceOwner',
        ])->getMock();

        $config = [
            'service' => OpenIDConnectService::class,
            'className' => $this->Provider,
            'mapper' => LinkedInOpenIDConnect::class,
            'authParams' => ['scope' => ['public_profile', 'email', 'user_birthday', 'user_gender', 'user_link']],
            'options' => [
                'state' => '__TEST_STATE__',
                'clientId' => '__CLIENT_ID__',
            ],
            'openid' => [
                'issuer' => 'https://www.linkedin.com/oauth',
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
            OpenIDConnectService::class,
        )->setConstructorArgs([
            $config,
        ])->onlyMethods([
            'getIdTokenKeys',
            'getHttpClient',
        ])->getMock();

        $this->Service->expects($this->any())
            ->method('getHttpClient')
            ->willReturn($this->Client);

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
            'className' => LinkedIn::class,
            'mapper' => LinkedInOpenIDConnect::class,
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

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Invalid OAuth2 state');

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

        $token = new AccessToken([
            'access_token' => 'test-token',
            'expires' => 1490988496,
        ]);

        $this->Provider->expects($this->once())
            ->method('getAccessToken')
            ->with(
                $this->equalTo('authorization_code'),
                $this->equalTo(['code' => 'ZPO9972j3092304230']),
            )
            ->willReturn($token);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Missing id_token in response');

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

        $token = new AccessToken([
            'access_token' => 'test-token',
            'expires' => 1490988496,
            'id_token' => 'invalid-jwt',
        ]);

        $this->Provider->expects($this->once())
            ->method('getAccessToken')
            ->with(
                $this->equalTo('authorization_code'),
                $this->equalTo(['code' => 'ZPO9972j3092304230']),
            )
            ->willReturn($token);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Invalid id token. Key may not be empty');

        $this->Service->getUser($this->Request);
    }

    /**
     * Build a signed id token and prepare the request, provider and key set mocks
     *
     * @param array $payload id token claims
     * @return \League\OAuth2\Client\Token\AccessToken
     */
    protected function prepareIdToken(array $payload): AccessToken
    {
        $this->Request = $this->Request->withQueryParams([
            'code' => 'ZPO9972j3092304230',
            'state' => '__TEST_STATE__',
        ]);
        $this->Request->getSession()->write('oauth2state', '__TEST_STATE__');
        $this->Request->getSession()->write(OpenIDConnectService::SESSION_NONCE_KEY, '__NONCE__');

        $key = 'example_key_with_at_least_32_bytes!';
        $alg = 'HS256';
        $kid = 'd929668a-bab1-4c69-9598-4373149723ff';
        $token = new AccessToken([
            'access_token' => 'test-token',
            'expires' => 1490988496,
            'id_token' => JWT::encode($payload, $key, $alg, $kid),
        ]);

        $this->Provider->expects($this->once())
            ->method('getAccessToken')
            ->with(
                $this->equalTo('authorization_code'),
                $this->equalTo(['code' => 'ZPO9972j3092304230']),
            )
            ->willReturn($token);
        $this->Service->expects($this->once())
            ->method('getIdTokenKeys')
            ->willReturn([$kid => new Key($key, $alg)]);

        return $token;
    }

    /**
     * Test GetUser
     *
     * @return void
     */
    public function testGetUser()
    {
        $payload = [
            'iat' => 1490988496,
            'iss' => 'https://www.linkedin.com/oauth',
            'aud' => '__CLIENT_ID__',
            'nonce' => '__NONCE__',
        ];
        $token = $this->prepareIdToken($payload);

        $actual = $this->Service->getUser($this->Request);

        $this->assertEquals(['token' => $token] + $payload, $actual);
        $this->assertNull($this->Request->getSession()->read(OpenIDConnectService::SESSION_NONCE_KEY));
    }

    /**
     * @return array<string, array{0: array, 1: string}>
     */
    public static function invalidClaimsProvider(): array
    {
        $valid = [
            'iss' => 'https://www.linkedin.com/oauth',
            'aud' => '__CLIENT_ID__',
            'nonce' => '__NONCE__',
        ];

        return [
            'wrong issuer' => [['iss' => 'https://evil.example.com'] + $valid, 'Unexpected issuer'],
            'missing issuer' => [array_diff_key($valid, ['iss' => true]), 'Unexpected issuer'],
            'wrong audience' => [['aud' => 'other-client'] + $valid, 'Unexpected audience'],
            'missing audience' => [array_diff_key($valid, ['aud' => true]), 'Unexpected audience'],
            'wrong nonce' => [['nonce' => 'other'] + $valid, 'Nonce mismatch'],
            'missing nonce' => [array_diff_key($valid, ['nonce' => true]), 'Nonce mismatch'],
        ];
    }

    /**
     * Test GetUser with invalid id token claims
     *
     * @param array $payload id token claims
     * @param string $message expected error
     * @return void
     */
    #[DataProvider('invalidClaimsProvider')]
    public function testGetUserInvalidClaims(array $payload, string $message): void
    {
        $this->prepareIdToken($payload);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage($message);

        $this->Service->getUser($this->Request);
    }

    /**
     * Test the authorization url carries a nonce stored in session
     *
     * @return void
     */
    public function testGetAuthorizationUrlNonce(): void
    {
        $this->Provider->expects($this->once())
            ->method('getAuthorizationUrl')
            ->with($this->callback(fn(array $params): bool => !empty($params['nonce']) &&
                $params['nonce'] === $this->Request->getSession()->read(OpenIDConnectService::SESSION_NONCE_KEY) &&
                $params['scope'] === ['public_profile', 'email', 'user_birthday', 'user_gender', 'user_link']))
            ->willReturn('http://example.com/auth');

        $this->assertSame('http://example.com/auth', $this->Service->getAuthorizationUrl($this->Request));
        $this->assertArrayNotHasKey('nonce', $this->Service->getConfig('authParams'));
    }

    /**
     * Test discover
     *
     * @return void
     */
    public function testDiscover()
    {
        $arrayTest = ['test' => 'test'];
        $response = new Response([], json_encode($arrayTest));

        $this->Client->expects($this->once())
            ->method('get')
            ->willReturn($response);

        $actual = $this->Service->discover();

        $this->assertEquals($arrayTest, $actual);
    }

    /**
     * Test discover uses the configured cache
     *
     * @return void
     */
    public function testDiscoverCached(): void
    {
        Cache::setConfig('_oidc_test_', ['className' => 'Array']);
        $this->Service->setConfig('openid.cache', '_oidc_test_');
        $arrayTest = ['issuer' => 'https://www.linkedin.com/oauth'];

        $this->Client->expects($this->once())
            ->method('get')
            ->willReturn(new Response([], json_encode($arrayTest)));

        $this->assertEquals($arrayTest, $this->Service->discover());
        $this->assertEquals($arrayTest, $this->Service->discover());
        Cache::drop('_oidc_test_');
    }
}
