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

use Cake\Network\Exception\ForbiddenException;
use CakeDC\Auth\Rbac\Rbac;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Check if the current logged in user has permission to access the
 * current action based on the request params
 *
 * Add this middleware after Authentication and Routes are processed as it will expect
 * the following keys present in the request
 * - 'identity'
 * - 'params'
 *
 * A ForbiddenException will be thrown in case the user is not allowed by an RBAC rule
 *
 * @package Middleware
 */
class RbacMiddleware
{
    /**
     * @var Rbac
     */
    protected $rbac;

    /**
     * RbacMiddleware constructor
     *
     * @param Rbac $rbac
     */
    public function __construct(Rbac $rbac = null)
    {
        if (!$rbac) {
            $rbac = new Rbac();
        }
        $this->rbac = $rbac;
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
        $userEntity = $request->getAttribute('identity');
        $userData = [];
        if ($userEntity) {
            $userData = $userEntity->toArray();
        }

        if (!$this->rbac->checkPermissions($userData, $request)) {
            throw new ForbiddenException();
        }

        return $next($request, $response);
    }
}
