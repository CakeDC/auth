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
use CakeDC\Auth\Social\Mapper\LinkedInOpenIDConnect;

class LinkedInOpenIDConnectTest extends TestCase
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
        $token = new \League\OAuth2\Client\Token\AccessToken([
            'access_token' => 'test-token',
            'expires' => 1490988496,
        ]);
        $rawData = [
            'sub' => '1',
            'token' => $token,
            'email' => 'test@gmail.com',           
            'given_name' => 'Test',
            'family_name' => 'User',
            'industry' => 'Computer Software',
            'location' => [
                'country' => [
                    'code' => 'es',
                ],
                'name' => 'Spain',
            ],
            'picture' => 'https://media.licdn.com/mpr/mprx/test.jpg',
            
            
            'bio' => 'The best test user in the world.',
            'publicProfileUrl' => 'https://www.linkedin.com/in/test',
        ];
        $providerMapper = new LinkedInOpenIDConnect();
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
            'link' => 'https://www.linkedin.com',
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
