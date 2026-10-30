<?php
declare(strict_types=1);

/*
 * Copyright 2010 - 2021, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010 - 2021, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Auth\Rbac\Rules;

use ArrayAccess;
use Cake\Utility\Hash;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Allows a user to act on its own record, the user id is the first passed param.
 * Used for the reset one time password authenticator action.
 */
class OTPRule extends AbstractRule
{
    /**
     * @inheritDoc
     */
    public function allowed(array|ArrayAccess $user, string $role, ServerRequestInterface $request): bool
    {
        $userId = Hash::get((array)$request->getAttribute('params'), 'pass.0');
        if (empty($userId) || empty($user['id'])) {
            return false;
        }

        return (string)$userId === (string)$user['id'];
    }
}
