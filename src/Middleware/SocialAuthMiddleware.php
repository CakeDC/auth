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

namespace CakeDC\Auth\Middleware;

use Authentication\UrlChecker\UrlCheckerTrait;
use CakeDC\Auth\Authenticator\SocialAuthenticator;
use CakeDC\Auth\Social\Service\ServiceFactory;
use Cake\Core\InstanceConfigTrait;
use Cake\Http\ServerRequest;
use Cake\Log\LogTrait;
use Psr\Http\Message\ResponseInterface;

class SocialAuthMiddleware
{
    use InstanceConfigTrait;
    use LogTrait;
    use UrlCheckerTrait;

    protected $_defaultConfig = [
        'loginUrl' => false,
        'urlChecker' => 'Authentication.Default'
    ];

    /**
     * SocialAuthMiddleware constructor.
     *
     * @param array $config optional configuration
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    /**
     * Perform social auth
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Message\ResponseInterface $response The response.
     * @param callable $next Callback to invoke the next middleware.
     * @return \Psr\Http\Message\ResponseInterface A response
     */
    public function __invoke(ServerRequest $request, ResponseInterface $response, $next)
    {
        if (!$this->checkUrl($request)) {
            return $next($request, $response);
        }

        $service = (new ServiceFactory())->createFromRequest($request);
        if (!$service->isGetUserStep($request)) {
            return $response->withLocation($service->getAuthorizationUrl($request));
        }
        $request = $request->withAttribute(SocialAuthenticator::SOCIAL_SERVICE_ATTRIBUTE, $service);

        return $next($request, $response);
    }

    /**
     * Check if is target url
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     *
     * @return bool
     */
    protected function checkUrl(ServerRequest $request)
    {
        return $this->_getUrlChecker()->check($request, $this->getConfig('loginUrl'));
    }
}
