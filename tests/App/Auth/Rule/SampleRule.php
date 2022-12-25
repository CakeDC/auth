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

namespace CakeDC\Auth\Test\App\Auth\Rule;

use ArrayAccess;
use CakeDC\Auth\Rbac\Rules\AbstractRule;
use Psr\Http\Message\ServerRequestInterface;

class SampleRule extends AbstractRule
{
    /**
     * @inheritDoc
     */
    public function allowed(array|ArrayAccess $user, string $role, ServerRequestInterface $request): bool
    {
        return true;
    }
}
