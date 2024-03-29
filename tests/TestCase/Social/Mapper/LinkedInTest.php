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
use CakeDC\Auth\Social\Mapper\LinkedIn;
use League\OAuth2\Client\Token\AccessToken;

class LinkedInTest extends TestCase
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
            'emailAddress' => 'test@gmail.com',
            'firstName' => 'Test',
            'headline' => 'The best test user in the world.',
            'id' => '1',
            'industry' => 'Computer Software',
            'lastName' => 'User',
            'location' => [
                'country' => [
                    'code' => 'es',
                ],
                'name' => 'Spain',
            ],
            'pictureUrl' => 'https://media.licdn.com/mpr/mprx/test.jpg',
            'publicProfileUrl' => 'https://www.linkedin.com/in/test',
        ];
        $providerMapper = new LinkedIn();
        $user = $providerMapper($rawData);
        $this->assertEquals([
            'id' => '1',
            'username' => null,
            'full_name' => null,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@gmail.com',
            'avatar' => 'https://media.licdn.com/mpr/mprx/test.jpg',
            'gender' => null,
            'link' => 'https://www.linkedin.com/in/test',
            'bio' => 'The best test user in the world.',
            'locale' => null,
            'validated' => true,
            'credentials' => [
                'token' => 'test-token',
                'secret' => null,
                'expires' => 1490988496,
            ],
            'raw' => $rawData,
        ], $user);
    }
}
