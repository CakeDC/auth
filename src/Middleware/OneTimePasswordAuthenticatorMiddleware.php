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

class OneTimePasswordAuthenticatorMiddleware
{
    /**
     * Proceed to second step of two factor authentication. See CakeDC\Auth\Controller\Traits\verify
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Message\ResponseInterface $response The response.
     * @param callable $next Callback to invoke the next middleware.
     * @return \Psr\Http\Message\ResponseInterface A response
     */
    public function __invoke(ServerRequest $request, ResponseInterface $response, $next)
    {
        $service = $request->getAttribute('authentication');

        if (!$service->getResult() || $service->getResult()->getStatus() !== AuthenticationService::NEED_TWO_FACTOR_VERIFY) {
            return $next($request, $response);
        }

        $request->getSession()->write(CookieAuthenticator::SESSION_DATA_KEY, [
            'remember_me' => $request->getData('remember_me')
        ]);

        $url = Router::url(Configure::read('OneTimePasswordAuthenticator.verifyAction'));

        return $response
            ->withHeader('Location', $url)
            ->withStatus(302);
    }
}
