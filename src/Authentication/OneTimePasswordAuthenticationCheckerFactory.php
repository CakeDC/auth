<?php
declare(strict_types=1);

/**
 * Copyright 2010 - 2026, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010 - 2026, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Auth\Authentication;

use Cake\Core\Configure;
use InvalidArgumentException;

/**
 * Factory for two authentication checker
 *
 * @package CakeDC\Auth\Authentication
 */
class OneTimePasswordAuthenticationCheckerFactory
{
    /**
     * Get the two factor authentication checker
     *
     * @return \CakeDC\Auth\Authentication\OneTimePasswordAuthenticationCheckerInterface
     */
    public function build(): OneTimePasswordAuthenticationCheckerInterface
    {
        $className = Configure::read('OneTimePasswordAuthenticator.checker');
        $required = OneTimePasswordAuthenticationCheckerInterface::class;

        if (is_string($className) && is_subclass_of($className, $required)) {
            return new $className();
        }
        throw new InvalidArgumentException(sprintf(
            "Invalid config for 'OneTimePasswordAuthenticator.checker', '%s' does not implement '%s'",
            is_string($className) ? $className : get_debug_type($className),
            $required,
        ));
    }
}
