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
}
