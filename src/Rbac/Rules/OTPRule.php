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

use App\Model\Entity\AppGroup;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use CakeDC\Auth\Rbac\Rules\AbstractRule;
use Psr\Http\Message\ServerRequestInterface;

class OTPRule extends AbstractRule
{
    /**
     * @inheritDoc
     */
    public function allowed($user, $role, ServerRequestInterface $request)
    {
        $userId = \Cake\Utility\Hash::get($request->getAttribute('params'), 'pass.0');
        if (!empty($userId) && !empty($user)) {
            return $userId === $user['id'];
        }

        return false;
    }
}
