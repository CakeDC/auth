<?php
/**
 * Copyright 2010 - 2017, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010 - 2017, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Auth\Middleware;

use CakeDC\Auth\Rbac\Rbac;
use Cake\Core\InstanceConfigTrait;
use Cake\Http\Exception\ForbiddenException;
use Cake\Routing\Router;
use Cake\Utility\Hash;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * WARNING: This middleware is considered alpha, until cakephp/authentication is completed
 *
 * Use it at your own risk
 *
 * =============================================================================================
 *
 * Check if the current logged in user has permission to access the
 * current action based on the request params
 *
 * Add this middleware after Authentication and Routes are processed as it will expect
 * the following keys present in the request
 * - 'identity'
 * - 'params'
 *
 * A ForbiddenException will be thrown in case the user is not allowed by an RBAC rule
 * There is currently a bypassAuth key defined in the rules to ignore if there is actually
 * a $user coming from Authentication, this could be handled in another Middleware before
 * the call to Rbac, possibly breaking the rules matcher into another class
 *
 * Example using this middleware along with cakephp/authentication
 * Add it after RoutingMiddleware in your Application.php file
 *
 * ...
 *
 * // Instantiate the service
 * $service = new AuthenticationService();
 *
 * $fields = [
 *     'username' => 'username',
 *     'password' => 'password'
 * ];
 *
 * // Load identifiers
 * $service->loadIdentifier('Authentication.Password', compact('fields'));
 *
 * // Load the authenticators, you want session first
 * $service->loadAuthenticator('Authentication.Session');
 * $service->loadAuthenticator('Authentication.Form', [
 *     'fields' => $fields,
 *     'loginUrl' => [
 *         'plugin' => 'CakeDC/Users',
 *         'controller' => 'Users',
 *         'action' => 'login',
 *     ]
 * ]);
 *
 * // Add it to the authentication middleware
 * $authentication = new AuthenticationMiddleware($service);
 *
 * // Add the middleware to the middleware queue
 * $middlewareQueue->add($authentication);
 *
 * $rbac = new RbacMiddleware();
 * $middlewareQueue->add($rbac);
 *
 *
 * @package Middleware
 */
class RbacMiddleware
{
    use InstanceConfigTrait;

    const UNAUTHORIZED_BEHAVIOR_THROW = 0;
    const UNAUTHORIZED_BEHAVIOR_REDIRECT = 1;
    const UNAUTHORIZED_BEHAVIOR_AUTO = 2;

    protected $_defaultConfig = [
        /*
         * Manage what to do if the request is not authorized,
         * throw - throw a ForbiddenException
         * redirect - redirect to loginAction
         * auto - check for json request, and throw or redirect
         */
        'unauthorizedBehavior' => self::UNAUTHORIZED_BEHAVIOR_REDIRECT,
        /*
         * Redirect to this url if the user is not authorized, depending on
         * the unauthorizedBehavior
         */
        'unauthorizedRedirect' => [
            'controller' => 'Users',
            'action' => 'login',
        ]
    ];

    /**
     * @var Rbac
     */
    protected $rbac;

    /**
     * RbacMiddleware constructor
     *
     * @param Rbac $rbac rbac instance
     * @param array $options options
     */
    public function __construct(Rbac $rbac = null, array $options = [])
    {
        if ($rbac === null) {
            $rbac = new Rbac();
        }
        $this->rbac = $rbac;

        $this->setConfig($options);
    }

    /**
     * Middleware logic
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request
     * @param \Psr\Http\Message\ResponseInterface $response The response
     * @param callable $next The next middleware to call
     * @return \Psr\Http\Message\ResponseInterface A response
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        $user = $request->getAttribute('identity');
        $userData = [];
        if ($user) {
            $userData = Hash::get($user, 'User', []);
            $userData = is_object($userData) ? $userData->toArray() : $userData;
        }

        if (!$this->rbac->checkPermissions($userData, $request)) {
            $response = $this->notAuthorized($userData, $request, $response);
        }
        $request = $request->withAttribute('rbac', $this->rbac);

        return $next($request, $response);
    }

    /**
     * Handles a not authorized request
     *
     * @param array $userData user data
     * @param ServerRequestInterface $request request
     * @param ResponseInterface $response response
     * @return ResponseInterface
     */
    protected function notAuthorized(array $userData, ServerRequestInterface $request, ResponseInterface $response)
    {
        $behavior = $this->getConfig('unauthorizedBehavior');

        if ($behavior === self::UNAUTHORIZED_BEHAVIOR_THROW) {
            throw new ForbiddenException();
        }

        if ($behavior === self::UNAUTHORIZED_BEHAVIOR_AUTO &&
            $request->getHeader('Accept') === 'application/json') {
            throw new ForbiddenException();
        }

        return $this->unauthorizedRedirect($response);
    }

    /**
     * Redirects to unauthorizedRedirect the response
     *
     * @param ResponseInterface $response response
     * @return ResponseInterface
     */
    protected function unauthorizedRedirect(ResponseInterface $response)
    {
        $url = $this->getConfig('unauthorizedRedirect');
        $redirectResponse = $response
            ->withAddedHeader('Location', Router::url($url, true))
            ->withStatus(302);

        return $redirectResponse;
    }
}
