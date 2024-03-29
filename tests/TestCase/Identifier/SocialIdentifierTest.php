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
namespace CakeDC\Auth\Test\TestCase\Identifier;

use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use CakeDC\Auth\Identifier\SocialIdentifier;
use CakeDC\Auth\Social\Mapper\Facebook;
use League\OAuth2\Client\Token\AccessToken;

class SocialIdentifierTest extends TestCase
{
    public array $fixtures = [
        'plugin.CakeDC/Auth.Users',
        'plugin.CakeDC/Auth.SocialAccounts',
    ];

    /**
     * Test identify method
     *
     * @return void
     */
    public function testIdentifyWithoutSocialAuthKey()
    {
        $identifier = new SocialIdentifier([]);

        $user = ['username' => ''];
        $result = $identifier->identify($user);
        $this->assertNull($result);

        $identifier = new SocialIdentifier([]);

        $user = [];
        $result = $identifier->identify($user);
        $this->assertNull($result);
    }

    /**
     * Test identify method
     *
     * @return void
     */
    public function testIdentify()
    {
        $identifier = new SocialIdentifier([
            'resolver' => 'Authentication.ORM',
        ]);

        $Token = new AccessToken([
            'access_token' => 'test-token',
            'expires' => 1490988496,
        ]);

        $data = [
            'token' => $Token,
            'id' => '1',
            'name' => 'Test User',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'user-1@test.com',
            'hometown' => [
                'id' => '108226049197930',
                'name' => 'Madrid',
            ],
            'picture' => [
                'data' => [
                    'url' => 'https://scontent.xx.fbcdn.net/v/test.jpg',
                    'is_silhouette' => false,
                ],
            ],
            'cover' => [
                'source' => 'https://scontent.xx.fbcdn.net/v/test.jpg',
                'id' => '1',
            ],
            'gender' => 'male',
            'locale' => 'en_US',
            'link' => 'https://www.facebook.com/app_scoped_user_id/1/',
            'timezone' => -5,
            'age_range' => [
                'min' => 21,
            ],
            'bio' => 'I am the best test user in the world.',
            'picture_url' => 'https://scontent.xx.fbcdn.net/v/test.jpg',
            'is_silhouette' => false,
            'cover_photo_url' => 'https://scontent.xx.fbcdn.net/v/test.jpg',
        ];

        $mapper = new Facebook();
        $user = $mapper($data);
        $user['provider'] = 'facebook';

        $result = $identifier->identify(['socialAuthUser' => $user]);
        $this->assertInstanceOf(Entity::class, $result);
        $this->assertEquals($result->id, '00000000-0000-0000-0000-000000000001');
        $this->assertEquals('user-1@test.com', $result->email);
        $this->assertEquals('user-1', $result->username);
    }

    /**
     * Test identify method error in social login
     *
     * @return void
     */
    public function testIdentifyErrorSocialLogin()
    {
        $Token = new AccessToken([
            'access_token' => 'test-token',
            'expires' => 1490988496,
        ]);

        $identifier = new SocialIdentifier([
            'resolver' => 'Authentication.ORM',
        ]);
        $data = [
            'token' => $Token,
            'id' => '1',
            'name' => '',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@gmail.com',
            'hometown' => [
                'id' => '108226049197930',
                'name' => 'Madrid',
            ],
            'locale' => 'en_US',
            'link' => 'https://www.facebook.com/app_scoped_user_id/1/',
            'timezone' => -5,
            'age_range' => [
                'min' => 21,
            ],
            'bio' => 'I am the best test user in the world.',
            'picture_url' => 'https://scontent.xx.fbcdn.net/v/test.jpg',
            'is_silhouette' => false,
            'cover_photo_url' => 'https://scontent.xx.fbcdn.net/v/test.jpg',
        ];

        $mapper = new Facebook();
        $user = $mapper($data);
        $user['provider'] = 'facebook';

        $result = $identifier->identify(['socialAuthUser' => $user]);
        $this->assertNull($result);
    }

    /**
     * Test identify method no email
     *
     * @return void
     */
    public function testIdentifyNoEmail()
    {
        $Token = new AccessToken([
            'access_token' => 'test-token',
            'expires' => 1490988496,
        ]);

        $data = [
            'token' => $Token,
            'id' => '1',
            'name' => '',
            'first_name' => 'Test',
            'last_name' => 'User',
            'hometown' => [
                'id' => '108226049197930',
                'name' => 'Madrid',
            ],
            'locale' => 'en_US',
            'link' => 'https://www.facebook.com/app_scoped_user_id/1/',
            'timezone' => -5,
            'age_range' => [
                'min' => 21,
            ],
            'bio' => 'I am the best test user in the world.',
            'picture_url' => 'https://scontent.xx.fbcdn.net/v/test.jpg',
            'is_silhouette' => false,
            'cover_photo_url' => 'https://scontent.xx.fbcdn.net/v/test.jpg',
        ];

        $identifier = new SocialIdentifier([]);
        $mapper = new Facebook();
        $user = $mapper($data);
        $user['provider'] = 'facebook';

        $result = $identifier->identify(['socialAuthUser' => $user]);
        $this->assertNull($result);
    }
}
