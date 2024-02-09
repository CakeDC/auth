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

namespace CakeDC\Auth\Middleware;

use Cake\Http\Response;
use Cake\Routing\Router;
use CakeDC\Auth\Authentication\TwoFactorProcessorLoader;
use CakeDC\Auth\Authenticator\CookieAuthenticator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TwoFactorMiddleware implements MiddlewareInterface
{
    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $service = $request->getAttribute('authentication');
        $status = $service->getResult() ? $service->getResult()->getStatus() : null;
        $processors = TwoFactorProcessorLoader::processors();
        $url = null;
        foreach ($processors as $processor) {
            $url = $processor->getUrlByType($status);
            if ($url !== null) {
                break;
            }
        }
        if ($url === null) {
            return $handler->handle($request);
        }

        /**
         * @var \Cake\Http\Session $session
         */
        $session = $request->getAttribute('session');
        $data = $request->getParsedBody();
        $data = is_array($data) ? $data : [];
        $session->write(CookieAuthenticator::SESSION_DATA_KEY, [
            'remember_me' => $data['remember_me'] ?? null,
        ]);
        $url = array_merge($url, [
            '?' => $request->getQueryParams(),
        ]);
        $url = Router::url($url);

        return (new Response())
            ->withHeader('Location', $url)
            ->withStatus(302);
    }
}
