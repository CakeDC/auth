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

namespace CakeDC\Auth\Traits;

use Authorization\AuthorizationServiceInterface;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Laminas\Diactoros\Uri;
use RuntimeException;

trait IsAuthorizedTrait
{
    /**
     * Returns true if the target url is authorized for the logged in user
     *
     * @param array|string|null $url url that the user is making request.
     * @param string $action Authorization action.
     * @return bool
     */
    public function isAuthorized(string|array|null $url = null, string $action = 'access'): bool
    {
        if (empty($url)) {
            return false;
        }

        if (is_array($url)) {
            return $this->_checkCanAccess(Router::normalize(Router::reverse($url)), $action);
        }

        return $this->_checkCanAccess($url, $action);
    }

    /**
     * Check if user can acces url
     *
     * @param string $url to check permissions
     * @param string $action Authorization action.
     * @return bool
     */
    protected function _checkCanAccess(string $url, string $action): bool
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
     * @return \Cake\Http\ServerRequest
     */
    protected function _createUrlRequestToCheck(string $url): ServerRequest
    {
        $uri = new Uri($url);
        $targetRequest = new ServerRequest([
            'uri' => $uri,
        ]);
        $params = Router::parseRequest($targetRequest);
        $targetRequest = $targetRequest->withAttribute('params', $params);

        return $targetRequest->withAttribute(
            'rbac',
            $this->getRequest()->getAttribute('rbac')
        );
    }
}
