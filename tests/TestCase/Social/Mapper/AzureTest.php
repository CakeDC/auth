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

namespace CakeDC\Auth\Test\TestCase\Social\Mapper;

use Cake\TestSuite\TestCase;
use CakeDC\Auth\Social\Mapper\Azure;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\DataProvider;

class AzureTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testMap()
    {
        $token = new AccessToken([
            'access_token' => 'test-token',
            'expires' => 1490988496,
        ]);
        $rawData = [
            'token' => $token,
            'aud' => 'ef044865-c304-4707-bce2-2c8cb469f093',
            'iss' => 'https://sts.windows.net/b5a44686-40bd-47e2-8e01-e52f214f2c8f/',
            'iat' => 1660940688,
            'nbf' => 1660940688,
            'exp' => 1660944588,
            'amr' => [ ],
            'ipaddr' => '127.0.0.1',
            'name' => 'Test',
            'oid' => '8a27cdf6-50c6-455b-af9d-ec60381ee8b9',
            'rh' => '0.AQUAhkaktb1A4keOAeUvIU8sj2VIBO8EwwdHvOIsjLRp8JMFAJM.',
            'sub' => 'wsAdh5DgzKg_-dz9xap8P0Sqnar2-CKifp0noideBv4',
            'tid' => 'b5a44686-40bd-47e2-8e01-e52f214f2c8f',
            'unique_name' => 'test@gmail.com',
            'upn' => 'test@gmail.com',
            'ver' => '1.0',
        ];
        $providerMapper = new Azure();
        $user = $providerMapper($rawData);
        $this->assertEquals([
            'id' => 'wsAdh5DgzKg_-dz9xap8P0Sqnar2-CKifp0noideBv4',
            'username' => 'test@gmail.com',
            'full_name' => 'Test',
            'first_name' => null,
            'last_name' => null,
            'email' => 'test@gmail.com',
            'avatar' => null,
            'gender' => null,
            'link' => '#',
            'bio' => null,
            'locale' => null,
            'validated' => false,
            'credentials' => [
                'token' => 'test-token',
                'secret' => null,
                'expires' => 1490988496,
            ],
            'raw' => $rawData,
        ], $user);
    }

    /**
     * @return array<string, array{0: array, 1: bool}>
     */
    public static function validatedProvider(): array
    {
        return [
            'only upn' => [[], false],
            'email_verified true' => [['email_verified' => true], true],
            'email_verified false' => [['email_verified' => false], false],
            'email_verified string' => [['email_verified' => 'true'], false],
            'xms_edov true' => [['xms_edov' => true], true],
        ];
    }

    /**
     * The email is only validated when Azure reports it as verified
     *
     * @param array $claims extra claims
     * @param bool $expected expected validated value
     * @return void
     */
    #[DataProvider('validatedProvider')]
    public function testValidated(array $claims, bool $expected): void
    {
        $rawData = $claims + [
            'token' => new AccessToken(['access_token' => 'test-token']),
            'sub' => 'user-id',
            'upn' => 'test@example.com',
        ];

        $this->assertSame($expected, (new Azure())($rawData)['validated']);
    }
}
