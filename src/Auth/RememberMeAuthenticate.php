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

namespace CakeDC\Auth\Auth;

use Cake\Auth\BaseAuthenticate;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Http\ServerRequest;

/**
 * Class RememberMeAuthenticate. Login the uses if a valid cookie is present
 */
class RememberMeAuthenticate extends BaseAuthenticate
{

    /**
     * Authenticate callback
     * Reads the stored cookie and auto login the user
     *
     * @param \Cake\Http\ServerRequest $request Cake request object.
     * @param Response $response Cake response object.
     * @return mixed
     */
    public function authenticate(ServerRequest $request, Response $response)
    {
        if (Configure::check('Users.RememberMe.active') && !Configure::read('Users.RememberMe.active')) {
            return false;
        }

        $cookieName = $this->getConfig('Cookie.name') ?:
            Configure::read('Users.RememberMe.Cookie.name') ?:
                'remember_me';
        if (!$this->_registry->getController()->Cookie) {
            $this->_registry->getController()->loadComponent('Cookie');
        }
        $cookie = $this->_registry->getController()->Cookie->read($cookieName);
        if (empty($cookie)) {
            return false;
        }
        $this->setConfig('fields.username', 'id');
        $user = $this->_findUser($cookie['id']);
        if ($user &&
            !empty($cookie['user_agent']) &&
            $request->getHeaderLine('User-Agent') === $cookie['user_agent']
        ) {
            return $user;
        }

        return false;
    }

    /**
     * Get a user based on a valid cookie
     *
     * Calls own class authenticate() method.
     *
     * Called from AuthComponent::_getUser() when there's no user info in
     * storage (e.g. after session time out).
     *
     * @param \Cake\Http\ServerRequest $request Request object.
     * @return mixed Either false or an array of user information
     */
    public function getUser(ServerRequest $request)
    {
        return $this->authenticate($request, new Response());
    }
}
