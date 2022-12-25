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

namespace CakeDC\Auth\Test\TestCase\Exception;

use Cake\TestSuite\TestCase;
use CakeDC\Auth\Exception\InvalidSettingsException;

class InvalidSettingsExceptionTest extends TestCase
{
    protected string $_messageTemplate = 'Invalid settings for key (%s)';
    protected int $code = 500;

    /**
     * Test construct
     */
    public function testConstruct()
    {
        $exception = new InvalidSettingsException('message');
        $this->assertEquals('message', $exception->getMessage());
    }
}
