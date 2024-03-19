<?php
declare(strict_types=1);

/**
 * Copyright 2010 - 2019, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010 - 2018, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace CakeDC\Auth\Authentication;

use Cake\Core\Configure;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Default class to check if two factor authentication is enabled and required
 *
 * @package CakeDC\Auth\Auth
 */
class DefaultCode2fAuthenticationChecker implements Code2fAuthenticationCheckerInterface
{
    /**
     * Check if two factor authentication is enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        if (!in_array(Configure::read('Code2f.type'), [self::CODE2F_TYPE_EMAIL, self::CODE2F_TYPE_PHONE])) {
            throw new \UnexpectedValueException(__d('cake_d_c/users', 'Code2F type must be: {0}, {1}', self::CODE2F_TYPE_EMAIL, self::CODE2F_TYPE_PHONE));
        }
        return Configure::read('Code2f.enabled') !== false;
    }

    /**
     * Check if two factor authentication is required for a user
     *
     * @param array $user user data
     * @return bool
     */
    public function isRequired(?array $user = null, ServerRequestInterface $request)
    {
        return !empty($user) && $this->isEnabled();
    }
}
