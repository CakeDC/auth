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

namespace CakeDC\Auth\Test\TestCase\Test;

use Cake\Core\Configure;
use CakeDC\Auth\Test\BaseTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class BaseTestTraitTest extends TestCase
{
    /**
     * Test the CSV rows are passed to `_testPermissions()`, comments and empty lines are skipped
     *
     * @return void
     */
    public function testPermissionsReadsCsv(): void
    {
        $csv = tempnam(sys_get_temp_dir(), 'permissions');
        file_put_contents($csv, implode("\n", [
            '# URL,USERNAME,METHOD,AJAX?,RESPONSE CODE EXPECTED,RESPONSE CONTAINS',
            '/admin/posts,admin,get,no-ajax,200,Posts',
            '',
            '/posts/delete/1,user,post,ajax,403',
        ]));
        $case = new class ('testPermissions') extends TestCase {
            use BaseTestTrait;

            public string $csvPath = '';

            public array $calls = [];

            /**
             * @return array
             */
            public static function provider(): array
            {
                return [];
            }

            protected function _permissionsCsvPath(string $csv): string
            {
                return $this->csvPath;
            }

            protected function _testPermissions(
                string $url,
                string $username,
                string $method,
                string $ajax,
                string $responseCode,
                string $responseContains = '',
            ): void {
                $this->calls[] = func_get_args();
            }
        };
        $case->csvPath = $csv;

        try {
            $case->testPermissions('permissions.csv');
        } finally {
            unlink($csv);
        }

        $this->assertSame([
            ['/admin/posts', 'admin', 'get', 'no-ajax', '200', 'Posts'],
            ['/posts/delete/1', 'user', 'post', 'ajax', '403', ''],
        ], $case->calls);
    }

    /**
     * Test the data provider is declared with an attribute, PHPUnit 12 ignores docblock annotations
     *
     * @return void
     */
    public function testPermissionsDataProviderAttribute(): void
    {
        $attributes = (new ReflectionMethod(BaseTestTrait::class, 'testPermissions'))
            ->getAttributes(DataProvider::class);

        $this->assertCount(1, $attributes);
        $this->assertSame(['provider'], $attributes[0]->getArguments());
    }

    /**
     * @return array<string, array{0: string|null, 1: array}>
     */
    public static function authSessionDataProvider(): array
    {
        $user = ['id' => 1, 'role' => 'admin'];

        return [
            'default key' => [null, ['Auth' => $user]],
            'CakeDC/Users key' => ['Auth', ['Auth' => $user]],
            'nested key' => ['Auth.User', ['Auth' => ['User' => $user]]],
        ];
    }

    /**
     * Test the user is stored at the session key of the session authenticator
     *
     * @param string|null $sessionKey `Auth.Authenticators.Session.sessionKey` value
     * @param array $expected expected session data
     * @return void
     */
    #[DataProvider('authSessionDataProvider')]
    public function testAuthSessionData(?string $sessionKey, array $expected): void
    {
        $backup = Configure::read('Auth.Authenticators.Session.sessionKey');
        if ($sessionKey === null) {
            Configure::delete('Auth.Authenticators.Session.sessionKey');
        } else {
            Configure::write('Auth.Authenticators.Session.sessionKey', $sessionKey);
        }
        $case = new class ('testAuthSessionData') extends TestCase {
            use BaseTestTrait;

            /**
             * @return array
             */
            public static function provider(): array
            {
                return [];
            }

            public function sessionData(array $user): array
            {
                return $this->_authSessionData($user);
            }
        };

        try {
            $this->assertSame($expected, $case->sessionData(['id' => 1, 'role' => 'admin']));
        } finally {
            Configure::write('Auth.Authenticators.Session.sessionKey', $backup);
        }
    }

    /**
     * Test the CSV method and ajax columns are case-insensitive
     *
     * @return void
     */
    public function testPermissionsMethodCaseInsensitive(): void
    {
        $case = new class ('testPermissions') extends TestCase {
            use BaseTestTrait;

            public array $requests = [];

            /**
             * @return array
             */
            public static function provider(): array
            {
                return [];
            }

            public function request(string $method, string $ajax): void
            {
                $this->_testPermissions('/posts/add', 'user', $method, $ajax, '200', 'ok');
            }

            public function loginAsUserName(string $username): void
            {
            }

            public function get(string $url): void
            {
                $this->requests[] = ['get', $url, $_SERVER['HTTP_X_REQUESTED_WITH'] ?? null];
            }

            public function post(string $url): void
            {
                $this->requests[] = ['post', $url, $_SERVER['HTTP_X_REQUESTED_WITH'] ?? null];
            }

            public function enableCsrfToken(): void
            {
            }

            public function enableSecurityToken(): void
            {
            }

            public function assertResponseOk(): void
            {
            }

            public function assertResponseContains(string $content): void
            {
            }
        };

        $backup = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? null;
        try {
            unset($_SERVER['HTTP_X_REQUESTED_WITH']);
            $case->request('POST', 'AJAX');
            unset($_SERVER['HTTP_X_REQUESTED_WITH']);
            $case->request('Get', 'no-ajax');
        } finally {
            if ($backup === null) {
                unset($_SERVER['HTTP_X_REQUESTED_WITH']);
            } else {
                $_SERVER['HTTP_X_REQUESTED_WITH'] = $backup;
            }
        }

        $this->assertSame([
            ['post', '/posts/add', 'XMLHttpRequest'],
            ['get', '/posts/add', null],
        ], $case->requests);
    }
}
