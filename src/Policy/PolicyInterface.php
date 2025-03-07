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

namespace CakeDC\Auth\Policy;

use Authorization\IdentityInterface;
use Authorization\Policy\ResultInterface;
use Psr\Http\Message\ServerRequestInterface;

interface PolicyInterface
{
    /**
     * Check permission
     *
     * @param \Authorization\IdentityInterface|null $identity
     * @param \Psr\Http\Message\ServerRequestInterface $resource
     * @return \Authorization\Policy\ResultInterface
     */
    public function canAccess(?IdentityInterface $identity, ServerRequestInterface $resource): ResultInterface;
}
