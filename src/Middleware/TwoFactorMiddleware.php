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

use CakeDC\Auth\Authentication\AuthenticationService;
use CakeDC\Auth\Authenticator\CookieAuthenticator;
use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Psr\Http\Message\ResponseInterface;

class TwoFactorMiddleware
{
    /**
     * Proceed to u2f flow or one-time password flow if needed.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Message\ResponseInterface $response The response.
     * @param callable $next Callback to invoke the next middleware.
     * @return \Psr\Http\Message\ResponseInterface A response
     */
    public function __invoke(ServerRequest $request, ResponseInterface $response, $next)
    {
        $service = $request->getAttribute('authentication');
        $status = $service->getResult() ? $service->getResult()->getStatus() : null;
        switch ($status) {
            case AuthenticationService::NEED_TWO_FACTOR_VERIFY:
                $url = Configure::read('OneTimePasswordAuthenticator.verifyAction');
                break;
            case AuthenticationService::NEED_U2F_VERIFY:
                $url = Configure::read('U2f.startAction');
                break;
            default:
                return $next($request, $response);
        }

        $request->getSession()->write(CookieAuthenticator::SESSION_DATA_KEY, [
            'remember_me' => $request->getData('remember_me')
        ]);
        $url = array_merge($url, [
            '?' => $request->getQueryParams()
        ]);
        $url = Router::url($url);

        return $response
            ->withHeader('Location', $url)
            ->withStatus(302);
    }
}
