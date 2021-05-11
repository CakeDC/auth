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

namespace CakeDC\Auth\Test\TestCase\Rbac\Rules;

use Cake\TestSuite\TestCase;
use CakeDC\Auth\Rbac\Rules\Owner;
use CakeDC\Auth\Rbac\Rules\RuleRegistry;

/**
 * @property Owner Owner
 * @property ServerRequest request
 */
class RuleRegistryTest extends TestCase
{
    /**
     * @return void
     */
    public function testGet()
    {
        $ownerRule = RuleRegistry::get(Owner::class, ['key' => 'value']);
        $ownerRule2 = RuleRegistry::get(Owner::class, ['key' => 'ignored']);

        $this->assertSame($ownerRule, $ownerRule2);
        $this->assertSame('value', $ownerRule->getConfig('key'));
        // the second instance is never created, configuration key is never used
        $this->assertSame('value', $ownerRule2->getConfig('key'));
    }
}
