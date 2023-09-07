<?php
declare(strict_types=1);

namespace CakeDC\Auth\Social\Service;

use Cake\Http\Client;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\ServerRequest;
use CakeDC\Auth\Social\Service\OAuth2Service;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Psr\Http\Message\ServerRequestInterface;

class OpenIDConnectService extends OAuth2Service
{
    protected $_defaultConfig = [
        'openid' => [
            'baseUrl' => 'https://www.linkedin.com/',
            'url' => 'https://www.linkedin.com/oauth/.well-known/openid-configuration',
            'jwk' => [
                'defaultAlgorithm' => 'RS256',
            ],
        ],
    ];

    public function getUser(ServerRequestInterface $request): array
    {
        if (!$request instanceof ServerRequest) {
            throw new \BadMethodCallException('Request must be an instance of ServerRequest');
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
        try {
            $idTokenDecoded = JWT::decode($idToken, $this->getIdTokenKeys());

            return ['token' => $token] + (array)$idTokenDecoded;
        } catch (\Exception $ex) {
            throw new BadRequestException('Invalid id token. ' . $ex->getMessage());
        }
    }

    protected function getIdTokenKeys(): array
    {
        $discoverData = $this->discover();
        $jwksUri = $discoverData['jwks_uri'] ?? null;
        if (!$jwksUri) {
            throw new BadRequestException(
                'No `jwks_uri` in discover data. Unable to retrieve the JWT signature public key'
            );
        }
        if (strpos($jwksUri, $this->getConfig('openid.baseUrl')) !== 0) {
            throw new BadRequestException(
                'Invalid `jwks_uri` in discover data. It is not pointing to ' .
                $this->getConfig('openid.baseUrl')
            );
        }
        $client = new Client();
        $jwksData = $client->get($jwksUri)->getJson();
        if (!$jwksData) {
            throw new BadRequestException(
                'Unable to retrieve jwks. Not found in the `jwks_uri` contents'
            );
        }

        return JWK::parseKeySet($jwksData, $this->getConfig('openid.jwk.defaultAlgorithm'));
    }

    public function discover(): array
    {
        $openidUrl = $this->getConfig('openid.url');
        $client = new Client();

        return $client->get($openidUrl)->getJson();
    }
}
