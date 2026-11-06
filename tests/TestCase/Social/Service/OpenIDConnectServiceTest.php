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
use Cake\Log\Log;
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
        $this->expectExceptionMessage('Invalid id token');

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
        $response = new Response(['HTTP/1.1 200 OK'], json_encode($arrayTest));

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
            ->willReturn(new Response(['HTTP/1.1 200 OK'], json_encode($arrayTest)));

        $this->assertEquals($arrayTest, $this->Service->discover());
        $this->assertEquals($arrayTest, $this->Service->discover());

        $service = $this->getMockBuilder(OpenIDConnectService::class)
            ->setConstructorArgs([$this->Service->getConfig()])
            ->onlyMethods(['getHttpClient'])
            ->getMock();
        $service->expects($this->never())->method('getHttpClient');
        $this->assertEquals($arrayTest, $service->discover());
        Cache::drop('_oidc_test_');
    }

    /**
     * Test discover returns an empty document when the request fails
     *
     * @return void
     */
    public function testDiscoverFailedRequest(): void
    {
        $this->Client->expects($this->once())
            ->method('get')
            ->willReturn(new Response(['HTTP/1.1 500 Internal Server Error'], json_encode(['issuer' => 'x'])));

        $this->assertSame([], $this->Service->discover());
    }

    /**
     * Test the key set is fetched again, bypassing the cache, when the token key id is unknown
     *
     * @return void
     */
    public function testGetUserKeyRotation(): void
    {
        $this->Service->setConfig('openid.cache', '_oidc_test_');
        $this->Request = $this->Request->withQueryParams([
            'code' => 'ZPO9972j3092304230',
            'state' => '__TEST_STATE__',
        ]);
        $this->Request->getSession()->write('oauth2state', '__TEST_STATE__');
        $this->Request->getSession()->write(OpenIDConnectService::SESSION_NONCE_KEY, '__NONCE__');

        $key = 'example_key_with_at_least_32_bytes!';
        $payload = [
            'iss' => 'https://www.linkedin.com/oauth',
            'aud' => '__CLIENT_ID__',
            'nonce' => '__NONCE__',
            'sub' => 'user-id',
        ];
        $token = new AccessToken([
            'access_token' => 'test-token',
            'expires' => 1490988496,
            'id_token' => JWT::encode($payload, $key, 'HS256', 'new-kid'),
        ]);
        $this->Provider->expects($this->once())
            ->method('getAccessToken')
            ->willReturn($token);
        $refreshes = [];
        $this->Service->expects($this->exactly(2))
            ->method('getIdTokenKeys')
            ->willReturnCallback(function (bool $refresh = false) use ($key, &$refreshes): array {
                $refreshes[] = $refresh;

                return [$refresh ? 'new-kid' : 'old-kid' => new Key($key, 'HS256')];
            });

        $user = $this->Service->getUser($this->Request);

        $this->assertSame([false, true], $refreshes);
        $this->assertSame('user-id', $user['sub']);
    }

    /**
     * Jwks uri served by the mocked http client
     *
     * @var string
     */
    protected const JWKS_URI = 'https://www.linkedin.com/oauth/openid/jwks';

    /**
     * Generate an RSA key pair
     *
     * @param string $kid key id
     * @return array{private: \OpenSSLAsymmetricKey, jwk: array}
     */
    protected function rsaKey(string $kid): array
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $details = openssl_pkey_get_details($key);
        $encode = fn(string $value): string => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');

        return [
            'private' => $key,
            'jwk' => [
                'kty' => 'RSA',
                'alg' => 'RS256',
                'use' => 'sig',
                'kid' => $kid,
                'n' => $encode($details['rsa']['n']),
                'e' => $encode($details['rsa']['e']),
            ],
        ];
    }

    /**
     * Service that only mocks the http client, so the key set is fetched and parsed
     *
     * @param array<string, \Cake\Http\Client\Response> $responses responses by url
     * @param array<string> $requested urls requested, filled by the mocked client
     * @return \CakeDC\Auth\Social\Service\OpenIDConnectService
     */
    protected function serviceWithHttp(array $responses, array &$requested = []): OpenIDConnectService
    {
        $this->Client->expects($this->any())
            ->method('get')
            ->willReturnCallback(function (string $url) use ($responses, &$requested): Response {
                $requested[] = $url;

                return $responses[$url] ?? new Response(['HTTP/1.1 404 Not Found'], '');
            });
        $service = $this->getMockBuilder(OpenIDConnectService::class)
            ->setConstructorArgs([$this->Service->getConfig()])
            ->onlyMethods(['getHttpClient'])
            ->getMock();
        $service->expects($this->any())
            ->method('getHttpClient')
            ->willReturn($this->Client);

        return $service;
    }

    /**
     * Json response
     *
     * @param array $data body
     * @return \Cake\Http\Client\Response
     */
    protected function jsonResponse(array $data): Response
    {
        return new Response(['HTTP/1.1 200 OK', 'Content-Type: application/json'], json_encode($data));
    }

    /**
     * Discovery and key set responses
     *
     * @param array $keys jwk keys
     * @param string $jwksUri jwks uri in the discovery document
     * @return array<string, \Cake\Http\Client\Response>
     */
    protected function oidcResponses(array $keys, string $jwksUri = self::JWKS_URI): array
    {
        return [
            'https://www.linkedin.com/oauth/.well-known/openid-configuration' => $this->jsonResponse([
                'issuer' => 'https://www.linkedin.com/oauth',
                'jwks_uri' => $jwksUri,
            ]),
            self::JWKS_URI => $this->jsonResponse(['keys' => $keys]),
        ];
    }

    /**
     * Prepare the callback request and the provider token response for an id token
     *
     * @param string $idToken encoded id token
     * @return void
     */
    protected function prepareCallback(string $idToken): void
    {
        $this->Request = $this->Request->withQueryParams([
            'code' => 'ZPO9972j3092304230',
            'state' => '__TEST_STATE__',
        ]);
        $this->Request->getSession()->write('oauth2state', '__TEST_STATE__');
        $this->Request->getSession()->write(OpenIDConnectService::SESSION_NONCE_KEY, '__NONCE__');
        $this->Provider->expects($this->once())
            ->method('getAccessToken')
            ->willReturn(new AccessToken([
                'access_token' => 'test-token',
                'id_token' => $idToken,
            ]));
    }

    /**
     * Valid id token claims
     *
     * @return array
     */
    protected function validClaims(): array
    {
        return [
            'iss' => 'https://www.linkedin.com/oauth',
            'aud' => '__CLIENT_ID__',
            'nonce' => '__NONCE__',
            'sub' => 'user-id',
            'exp' => time() + 300,
        ];
    }

    /**
     * Assert getUser fails with the generic error and logs the reason
     *
     * @param \CakeDC\Auth\Social\Service\OpenIDConnectService $service service
     * @param string $logged expected text in the log
     * @return void
     */
    protected function assertInvalidIdToken(OpenIDConnectService $service, string $logged): void
    {
        Log::setConfig('_oidc_test_', ['className' => 'Array', 'levels' => ['warning']]);
        try {
            $service->getUser($this->Request);
            $this->fail('BadRequestException was not thrown');
        } catch (BadRequestException $ex) {
            $this->assertSame('Invalid id token', $ex->getMessage());
            $this->assertStringContainsString($logged, implode("\n", Log::engine('_oidc_test_')->read()));
        } finally {
            Log::drop('_oidc_test_');
        }
    }

    /**
     * Test a RS256 id token is verified with the key set from the discovery document
     *
     * @return void
     */
    public function testGetUserRs256(): void
    {
        $key = $this->rsaKey('key-1');
        $requested = [];
        $service = $this->serviceWithHttp($this->oidcResponses([$key['jwk']]), $requested);
        $this->prepareCallback(JWT::encode($this->validClaims(), $key['private'], 'RS256', 'key-1'));

        $user = $service->getUser($this->Request);

        $this->assertSame('user-id', $user['sub']);
        $this->assertSame([
            'https://www.linkedin.com/oauth/.well-known/openid-configuration',
            self::JWKS_URI,
        ], $requested);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function rejectedIdTokenProvider(): array
    {
        return [
            'signed with another key' => ['otherKey', 'Signature verification failed'],
            'algorithm mismatch' => ['hs256', 'Incorrect key for this algorithm'],
            'unsigned' => ['none', 'Algorithm not supported'],
            'unknown key id without cache' => ['unknownKid', '"kid" invalid'],
            'expired' => ['expired', 'Expired token'],
        ];
    }

    /**
     * Test id tokens that must be rejected by the signature verification
     *
     * @param string $case token case
     * @param string $logged expected logged reason
     * @return void
     */
    #[DataProvider('rejectedIdTokenProvider')]
    public function testGetUserRejectedIdToken(string $case, string $logged): void
    {
        $key = $this->rsaKey('key-1');
        $requested = [];
        $service = $this->serviceWithHttp($this->oidcResponses([$key['jwk']]), $requested);
        $claims = $this->validClaims();
        $encode = fn(array $data): string => rtrim(strtr(base64_encode(json_encode($data)), '+/', '-_'), '=');
        $idToken = match ($case) {
            'otherKey' => JWT::encode($claims, $this->rsaKey('key-1')['private'], 'RS256', 'key-1'),
            'hs256' => JWT::encode($claims, str_repeat('s', 32), 'HS256', 'key-1'),
            'none' => $encode(['alg' => 'none', 'typ' => 'JWT', 'kid' => 'key-1']) . '.' . $encode($claims) . '.',
            'unknownKid' => JWT::encode($claims, $key['private'], 'RS256', 'key-2'),
            'expired' => JWT::encode(['exp' => time() - 300] + $claims, $key['private'], 'RS256', 'key-1'),
        };
        $this->prepareCallback($idToken);

        $this->assertInvalidIdToken($service, $logged);
        $this->assertCount(2, $requested, 'The key set is not fetched again without cache');
    }

    /**
     * Test the key set is not fetched from a jwks uri outside of `openid.baseUrl`
     *
     * @return void
     */
    public function testGetUserForeignJwksUri(): void
    {
        $key = $this->rsaKey('key-1');
        $requested = [];
        $service = $this->serviceWithHttp(
            $this->oidcResponses([$key['jwk']], 'https://evil.example.com/jwks'),
            $requested,
        );
        $this->prepareCallback(JWT::encode($this->validClaims(), $key['private'], 'RS256', 'key-1'));

        $this->assertInvalidIdToken($service, 'Invalid `jwks_uri` in discover data');
        $this->assertNotContains('https://evil.example.com/jwks', $requested);
    }

    /**
     * Test a discovery document without `jwks_uri`
     *
     * @return void
     */
    public function testGetUserMissingJwksUri(): void
    {
        $key = $this->rsaKey('key-1');
        $responses = $this->oidcResponses([$key['jwk']]);
        $responses['https://www.linkedin.com/oauth/.well-known/openid-configuration'] = $this->jsonResponse([
            'issuer' => 'https://www.linkedin.com/oauth',
        ]);
        $service = $this->serviceWithHttp($responses);
        $this->prepareCallback(JWT::encode($this->validClaims(), $key['private'], 'RS256', 'key-1'));

        $this->assertInvalidIdToken($service, 'No `jwks_uri` in discover data');
    }

    /**
     * Test a key set request that fails
     *
     * @return void
     */
    public function testGetUserJwksRequestFailed(): void
    {
        $key = $this->rsaKey('key-1');
        $responses = $this->oidcResponses([$key['jwk']]);
        $responses[self::JWKS_URI] = new Response(['HTTP/1.1 503 Service Unavailable'], '');
        $service = $this->serviceWithHttp($responses);
        $this->prepareCallback(JWT::encode($this->validClaims(), $key['private'], 'RS256', 'key-1'));

        $this->assertInvalidIdToken($service, 'Unable to retrieve jwks');
    }

    /**
     * Test a cached key set without the token key id is fetched again
     *
     * @return void
     */
    public function testGetUserCachedKeySetRotation(): void
    {
        Cache::setConfig('_oidc_test_', ['className' => 'Array']);
        $oldKey = $this->rsaKey('old-key');
        $newKey = $this->rsaKey('new-key');
        Cache::write('cakedc_auth_oidc_' . md5(self::JWKS_URI), ['keys' => [$oldKey['jwk']]], '_oidc_test_');
        $requested = [];
        $service = $this->serviceWithHttp($this->oidcResponses([$oldKey['jwk'], $newKey['jwk']]), $requested);
        $service->setConfig('openid.cache', '_oidc_test_');
        $this->prepareCallback(JWT::encode($this->validClaims(), $newKey['private'], 'RS256', 'new-key'));

        try {
            $user = $service->getUser($this->Request);
            $cached = Cache::read('cakedc_auth_oidc_' . md5(self::JWKS_URI), '_oidc_test_');
        } finally {
            Cache::drop('_oidc_test_');
        }

        $this->assertSame('user-id', $user['sub']);
        $this->assertSame([
            'https://www.linkedin.com/oauth/.well-known/openid-configuration',
            self::JWKS_URI,
        ], $requested);
        $this->assertSame(['old-key', 'new-key'], array_column($cached['keys'], 'kid'));
    }

    /**
     * Test the authorization url has no nonce when `openid.nonce` is disabled
     *
     * @return void
     */
    public function testGetAuthorizationUrlWithoutNonce(): void
    {
        $this->Service->setConfig('openid.nonce', false);
        $this->Provider->expects($this->once())
            ->method('getAuthorizationUrl')
            ->with($this->callback(fn(array $params): bool => !isset($params['nonce'])))
            ->willReturn('http://example.com/auth');

        $this->assertSame('http://example.com/auth', $this->Service->getAuthorizationUrl($this->Request));
        $this->assertNull($this->Request->getSession()->read(OpenIDConnectService::SESSION_NONCE_KEY));
    }
}
