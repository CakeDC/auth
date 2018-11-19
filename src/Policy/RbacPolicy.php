<?php
/**
 * Copyright 2010 - 2018, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010 - 2018, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Auth\Policy;

use CakeDC\Auth\Rbac\Rbac;
use Psr\Http\Message\ServerRequestInterface;

class RbacPolicy
{
    /**
     * Check rbac permission
     *
     * @param \Authorization\IdentityInterface|null $identity user identity
     * @param ServerRequestInterface $resource server request
     * @return bool
     */
    public function canAccess($identity, $resource)
    {
        $rbac = $resource->getAttribute('rbac');
        if ($rbac === null) {
            $rbac = new Rbac();
        }

        $user = $identity ? $identity->getOriginalData()->toArray() : [];

        return (bool)$rbac->checkPermissions($user, $resource);
    }
}
