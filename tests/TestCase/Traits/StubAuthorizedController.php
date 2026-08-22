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
namespace CakeDC\Auth\Test\TestCase\Traits;

use Cake\Http\ServerRequest;
use CakeDC\Auth\Traits\IsAuthorizedTrait;

/**
 * Concrete class using the trait for mocking in PHPUnit 12+
 */
class StubAuthorizedController
{
    use IsAuthorizedTrait;

    private ServerRequest $stubRequest;

    public function getRequest(): ServerRequest
    {
        return $this->stubRequest;
    }

    public function setStubRequest(ServerRequest $request): void
    {
        $this->stubRequest = $request;
    }
}
