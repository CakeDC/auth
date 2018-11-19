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

namespace CakeDC\Auth\Identifier;

use Authentication\Identifier\AbstractIdentifier;
use Authentication\Identifier\Resolver\ResolverAwareTrait;
use Cake\Core\Configure;
use Cake\ORM\Locator\LocatorAwareTrait;

class SocialIdentifier extends AbstractIdentifier
{
    use ResolverAwareTrait;

    const CREDENTIAL_KEY = 'socialAuthUser';

    /**
     * Identifies an user or service by the passed credentials
     *
     * @param array $credentials Authentication credentials
     * @return \ArrayAccess|array|null
     */
    public function identify(array $credentials)
    {
        if (!isset($credentials[self::CREDENTIAL_KEY]['email'])) {
            return null;
        }

        return $this->getResolver()->find([
            'email' => $credentials[self::CREDENTIAL_KEY]['email']
        ]);
    }
}
