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

namespace CakeDC\Auth\Test\TestCase\Rbac\Rules;

use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use CakeDC\Auth\Rbac\Rules\OTPRule;
use PHPUnit\Framework\Attributes\DataProvider;

class OTPRuleTest extends TestCase
{
    /**
     * @return array<string, array{0: array, 1: array, 2: bool}>
     */
    public static function allowedProvider(): array
    {
        return [
            'own uuid' => [['id' => '00000000-0000-0000-0000-000000000001'], ['00000000-0000-0000-0000-000000000001'], true],
            'own integer id' => [['id' => 1], ['1'], true],
            'other user' => [['id' => 1], ['2'], false],
            'no pass' => [['id' => 1], [], false],
            'no user id' => [[], ['1'], false],
        ];
    }

    /**
     * @param array $user user
     * @param array $pass passed params
     * @param bool $expected expected result
     * @return void
     */
    #[DataProvider('allowedProvider')]
    public function testAllowed(array $user, array $pass, bool $expected): void
    {
        $request = (new ServerRequest())->withParam('pass', $pass);

        $this->assertSame($expected, (new OTPRule())->allowed($user, 'user', $request));
    }
}
