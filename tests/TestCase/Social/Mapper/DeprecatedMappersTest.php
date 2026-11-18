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

namespace CakeDC\Auth\Test\TestCase\Social\Mapper;

use Cake\TestSuite\TestCase;
use CakeDC\Auth\Social\Mapper\AbstractMapper;
use CakeDC\Auth\Social\Mapper\Instagram;
use CakeDC\Auth\Social\Mapper\LinkedIn;
use CakeDC\Auth\Social\Mapper\Pinterest;
use CakeDC\Auth\Social\Mapper\Tumblr;
use PHPUnit\Framework\Attributes\DataProvider;

class DeprecatedMappersTest extends TestCase
{
    /**
     * @return array<string, array{0: class-string<\CakeDC\Auth\Social\Mapper\AbstractMapper>}>
     */
    public static function deprecatedMapperProvider(): array
    {
        return [
            'LinkedIn' => [LinkedIn::class],
            'Instagram' => [Instagram::class],
            'Pinterest' => [Pinterest::class],
            'Tumblr' => [Tumblr::class],
        ];
    }

    /**
     * Test the deprecated mappers emit a deprecation warning and still work
     *
     * @param class-string<\CakeDC\Auth\Social\Mapper\AbstractMapper> $className mapper class
     * @return void
     */
    #[DataProvider('deprecatedMapperProvider')]
    public function testDeprecationWarning(string $className): void
    {
        $mapper = null;
        $this->deprecated(function () use (&$mapper, $className): void {
            $mapper = new $className();
        });

        $this->assertInstanceOf(AbstractMapper::class, $mapper);
    }

    /**
     * Test the deprecation warning is raised
     *
     * @param class-string<\CakeDC\Auth\Social\Mapper\AbstractMapper> $className mapper class
     * @return void
     */
    #[DataProvider('deprecatedMapperProvider')]
    public function testDeprecationMessage(string $className): void
    {
        $this->expectDeprecationMessageMatches('/mapper is deprecated/', function () use ($className): void {
            new $className();
        });
    }
}
