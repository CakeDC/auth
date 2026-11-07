<?php
declare(strict_types=1);

/**
 * Copyright 2010 - 2026, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010 - 2026, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Auth\Social\Service;

use BadMethodCallException;
use Cake\Cache\Cache;
use Cake\Http\Client;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\ServerRequest;
use Cake\Log\LogTrait;
use Exception;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LogLevel;
use RuntimeException;
use UnexpectedValueException;

class OpenIDConnectService extends OAuth2Service
{
    use LogTrait;

    /**
     * Session key used to store the nonce sent in the authorization request
     *
     * @var string
     */
    public const SESSION_NONCE_KEY = 'oidcnonce';

    /**
     * Discovery document, fetched once per instance
     *
     * @var array|null
     */
    protected ?array $discoveryDocument = null;

    /**
     * Default configuration. `openid` defaults target LinkedIn, override them per provider.
     *
     * - `openid.baseUrl`: the `jwks_uri` from the discovery document must use its scheme, host and port,
     *   and a path below its path.
     * - `openid.url`: discovery document url.
     * - `openid.issuer`: expected `iss` claim, null to use the discovery document `issuer`.
     * - `openid.nonce`: send a nonce in the authorization request and validate it in the id token.
     * - `openid.cache`: cache config used for the discovery document and the key set, null to disable.
     * - `openid.jwk.defaultAlgorithm`: algorithm used for keys without `alg`.
     *
     * @var array
     */
    protected array $_defaultConfig = [
        'openid' => [
            'baseUrl' => 'https://www.linkedin.com/',
            'url' => 'https://www.linkedin.com/oauth/.well-known/openid-configuration',
            'issuer' => null,
            'nonce' => true,
            'cache' => null,
            'jwk' => [
                'defaultAlgorithm' => 'RS256',
            ],
        ],
    ];

    /**
     * @inheritDoc
     */
    public function getAuthorizationUrl(ServerRequestInterface $request): string
    {
        if (!$request instanceof ServerRequest) {
            throw new BadMethodCallException('Request must be an instance of ServerRequest');
        }
        if (!$this->getConfig('openid.nonce')) {
            return parent::getAuthorizationUrl($request);
        }
        $nonce = bin2hex(random_bytes(16));
        $request->getSession()->write(self::SESSION_NONCE_KEY, $nonce);
        $authParams = $this->getConfig('authParams', []);
        $this->setConfig('authParams', ['nonce' => $nonce] + $authParams, false);
        try {
            return parent::getAuthorizationUrl($request);
        } finally {
            $this->setConfig('authParams', $authParams, false);
        }
    }

    /**
     * Get the user from the validated id token
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request object.
     * @throws \Cake\Http\Exception\BadRequestException when state or id token are invalid
     * @return array
     */
    public function getUser(ServerRequestInterface $request): array
    {
        if (!$request instanceof ServerRequest) {
            throw new BadMethodCallException('Request must be an instance of ServerRequest');
        }
        if (!$this->validate($request)) {
            throw new BadRequestException('Invalid OAuth2 state');
        }

        $code = $request->getQuery('code');
        /** @var \League\OAuth2\Client\Token\AccessToken $token */
        $token = $this->provider->getAccessToken('authorization_code', ['code' => $code]);
        $tokenValues = $token->getValues();
        $idToken = $tokenValues['id_token'] ?? null;
        if (!$idToken) {
            throw new BadRequestException('Missing id_token in response');
        }
        if (!class_exists(JWT::class)) {
            throw new RuntimeException('OpenIDConnectService requires the firebase/php-jwt package');
        }
        try {
            $claims = $this->decodeIdToken($idToken);
        } catch (Exception $ex) {
            $this->log('Invalid OpenID Connect id token: ' . $ex->getMessage(), LogLevel::WARNING);

            throw new BadRequestException('Invalid id token');
        }
        $this->validateClaims($request, $claims);

        return ['token' => $token] + $claims;
    }

    /**
     * Validates the `iss`, `aud` and `nonce` claims of the id token
     *
     * @param \Cake\Http\ServerRequest $request Request object.
     * @param array $claims Decoded id token claims.
     * @throws \Cake\Http\Exception\BadRequestException when a claim is invalid
     * @return void
     */
    protected function validateClaims(ServerRequest $request, array $claims): void
    {
        $issuer = $this->getConfig('openid.issuer') ?? ($this->discover()['issuer'] ?? null);
        if (!$issuer || ($claims['iss'] ?? null) !== $issuer) {
            throw new BadRequestException('Invalid id token. Unexpected issuer');
        }

        $clientId = $this->getConfig('options.clientId');
        $audience = (array)($claims['aud'] ?? []);
        if (!$clientId || !in_array($clientId, $audience, true)) {
            throw new BadRequestException('Invalid id token. Unexpected audience');
        }
        $authorizedParty = $claims['azp'] ?? null;
        if (($authorizedParty !== null || count($audience) > 1) && $authorizedParty !== $clientId) {
            throw new BadRequestException('Invalid id token. Unexpected authorized party');
        }

        if ($this->getConfig('openid.nonce')) {
            $session = $request->getSession();
            $nonce = $session->read(self::SESSION_NONCE_KEY);
            $session->delete(self::SESSION_NONCE_KEY);
            if (!is_string($nonce) || !hash_equals($nonce, (string)($claims['nonce'] ?? ''))) {
                throw new BadRequestException('Invalid id token. Nonce mismatch');
            }
        }
    }

    /**
     * Decode the id token and verify its signature
     *
     * When the token is signed with a key that is not in the cached key set, the provider may have
     * rotated its keys, so the key set is fetched again once, bypassing the cache.
     *
     * @param string $idToken Encoded id token.
     * @return array
     */
    protected function decodeIdToken(string $idToken): array
    {
        try {
            return (array)JWT::decode($idToken, $this->getIdTokenKeys());
        } catch (UnexpectedValueException $ex) {
            if (!$this->getConfig('openid.cache') || !str_contains($ex->getMessage(), '"kid" invalid')) {
                throw $ex;
            }

            return (array)JWT::decode($idToken, $this->getIdTokenKeys(true));
        }
    }

    /**
     * Get the keys used to verify the id token signature
     *
     * @param bool $refresh Fetch the key set again instead of reading it from the cache.
     * @return array<string, \Firebase\JWT\Key>
     */
    protected function getIdTokenKeys(bool $refresh = false): array
    {
        $discoverData = $this->discover();
        $jwksUri = $discoverData['jwks_uri'] ?? null;
        if (!$jwksUri) {
            throw new BadRequestException(
                'No `jwks_uri` in discover data. Unable to retrieve the JWT signature public key',
            );
        }
        if (!is_string($jwksUri) || !$this->isTrustedUrl($jwksUri, (string)$this->getConfig('openid.baseUrl'))) {
            throw new BadRequestException(
                'Invalid `jwks_uri` in discover data. It is not pointing to ' .
                $this->getConfig('openid.baseUrl'),
            );
        }
        $jwksData = $this->fetchJson($jwksUri, $refresh);
        if (!$jwksData) {
            throw new BadRequestException(
                'Unable to retrieve jwks. Not found in the `jwks_uri` contents',
            );
        }

        return JWK::parseKeySet($jwksData, $this->getConfig('openid.jwk.defaultAlgorithm'));
    }

    /**
     * Check the url uses the scheme, host and port of the base url, and a path below its path
     *
     * @param string $url Url to check.
     * @param string $baseUrl Trusted base url, `openid.baseUrl`.
     * @return bool
     */
    protected function isTrustedUrl(string $url, string $baseUrl): bool
    {
        $target = parse_url($url);
        $base = parse_url($baseUrl);
        if (!is_array($target) || !is_array($base)) {
            return false;
        }
        $targetHost = strtolower($target['host'] ?? '');
        $baseHost = strtolower($base['host'] ?? '');
        if ($baseHost === '' || $targetHost !== $baseHost) {
            return false;
        }
        if (
            strtolower($target['scheme'] ?? '') !== strtolower($base['scheme'] ?? '') ||
            ($target['port'] ?? null) !== ($base['port'] ?? null)
        ) {
            return false;
        }
        $basePath = rtrim($base['path'] ?? '', '/');
        $path = $target['path'] ?? '/';

        return $basePath === '' || $path === $basePath || str_starts_with($path, $basePath . '/');
    }

    /**
     * Get the OpenID discovery document, fetched once per service instance
     *
     * @return array
     */
    public function discover(): array
    {
        return $this->discoveryDocument ??= $this->fetchJson($this->getConfig('openid.url'));
    }

    /**
     * Fetch a json document, using the `openid.cache` cache config when set
     *
     * @param string $url Url of the document.
     * @param bool $refresh Ignore the cached document and fetch it again.
     * @return array The document, empty when the request failed.
     */
    protected function fetchJson(string $url, bool $refresh = false): array
    {
        $fetch = function () use ($url): array {
            $response = $this->getHttpClient()->get($url);
            if (!$response->isOk()) {
                $this->log(
                    sprintf('OpenID Connect request to %s failed with status %s', $url, $response->getStatusCode()),
                    LogLevel::WARNING,
                );

                return [];
            }

            return (array)$response->getJson();
        };
        $cacheConfig = $this->getConfig('openid.cache');
        if (!$cacheConfig) {
            return $fetch();
        }

        $key = 'cakedc_auth_oidc_' . md5($url);
        $data = $refresh ? null : Cache::read($key, $cacheConfig);
        if (is_array($data) && $data) {
            return $data;
        }
        $data = $fetch();
        if ($data) {
            Cache::write($key, $data, $cacheConfig);
        }

        return $data;
    }

    /**
     * Get the http client
     *
     * @return \Cake\Http\Client
     */
    protected function getHttpClient(): Client
    {
        return new Client();
    }
}
