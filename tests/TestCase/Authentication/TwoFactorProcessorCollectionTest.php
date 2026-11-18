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

namespace CakeDC\Auth\Test\TestCase\Authentication;

use Cake\TestSuite\TestCase;
use CakeDC\Auth\Authentication\TwoFactorProcessor\OneTimePasswordProcessor;
use CakeDC\Auth\Authentication\TwoFactorProcessorCollection;
use InvalidArgumentException;
use stdClass;

class TwoFactorProcessorCollectionTest extends TestCase
{
    /**
     * Test processors can be added as instances and as class names
     *
     * @return void
     */
    public function testAddMany(): void
    {
        $collection = new TwoFactorProcessorCollection([
            OneTimePasswordProcessor::class,
            new OneTimePasswordProcessor(),
        ]);

        $this->assertCount(2, $collection);
        foreach ($collection as $processor) {
            $this->assertInstanceOf(OneTimePasswordProcessor::class, $processor);
        }
    }

    /**
     * Test a class name that is not a two factor processor throws
     *
     * @return void
     */
    public function testAddManyInvalidClass(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not implement');

        new TwoFactorProcessorCollection([stdClass::class]);
    }
}
