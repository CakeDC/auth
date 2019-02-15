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

namespace CakeDC\Auth\Traits;

use Authorization\AuthorizationServiceInterface;
use CakeDC\Auth\Rbac\Rbac;
use Cake\Http\ServerRequest;
use Cake\Routing\Exception\MissingRouteException;
use Cake\Routing\Router;
use Zend\Diactoros\Uri;

trait IsAuthorizedTrait
{
    /**
     * Returns true if the target url is authorized for the logged in user
     *
     * @param string|array|null $url url that the user is making request.
     * @param string $action Authorization action.
     *
     * @return bool
     */
    public function isAuthorized($url = null, $action = 'access')
    {
        if (empty($url)) {
            return false;
        }

        if (is_array($url)) {
            return $this->_checkCanAccess(Router::normalize(Router::reverse($url)), $action);
        }

        try {
            //remove base from $url if exists
            $normalizedUrl = Router::normalize($url);

            return $this->_checkCanAccess($url, $action);
        } catch (MissingRouteException $ex) {
            //if it's a url pointing to our own app
            if (substr($normalizedUrl, 0, 1) === '/') {
                throw $ex;
            }

            return true;
        }
    }

    /**
     * Check if user can acces url
     *
     * @param string $url to check permissions
     * @param string $action Authorization action.
     *
     * @return bool
     */
    protected function _checkCanAccess($url, $action)
    {
        /**
         * @var \Cake\Http\ServerRequest $request
         */
        $request = $this->getRequest();
        $service = $request->getAttribute('authorization');
        if (!$service instanceof AuthorizationServiceInterface) {
            throw new RuntimeException(__('Could not find the authorization service in the request.'));
        }
        $identity = $request->getAttribute('identity');
        $targetRequest = $this->_createUrlRequestToCheck($url);

        return $service->can($identity, $action, $targetRequest);
    }

    /**
     * Create the url request to check authorization
     *
     * @param string $url The target url.
     *
     * @return ServerRequest
     */
    protected function _createUrlRequestToCheck($url)
    {
        $uri = new Uri($url);
        $targetRequest = new ServerRequest([
            'uri' => $uri
        ]);
        $params = Router::parseRequest($targetRequest);
        $targetRequest = $targetRequest->withAttribute('params', $params);
        $targetRequest = $targetRequest->withAttribute(
            'rbac',
            $this->getRequest()->getAttribute('rbac')
        );

        return $targetRequest;
    }

    /**
     * Check if current user permissions of url
     *
     * @param string $url to check permissions
     *
     * @return bool
     */
    protected function checkRbacPermissions($url)
    {
        $uri = new Uri($url);
        $request = $this->getRequest();
        $Rbac = $request->getAttribute('rbac');
        if ($Rbac === null) {
            $Rbac = new Rbac();
        }
        $targetRequest = new ServerRequest([
            'uri' => $uri
        ]);
        $params = Router::parseRequest($targetRequest);
        $targetRequest = $targetRequest->withAttribute('params', $params);

        $user = $request->getAttribute('identity');
        $userData = [];
        if ($user) {
            $userData = $user->getOriginalData()->toArray();
        }

        return $Rbac->checkPermissions($userData, $targetRequest);
    }
}
