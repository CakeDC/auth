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

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use CakeDC\Auth\Social\Mapper\Keycloak;
use League\OAuth2\Client\Token\AccessToken;
use UnexpectedValueException;

class KeycloakTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Configure::write('OAuth.providers.keycloak.rolesMap', [
            'CakeDc-Admin' => 'admin',
            'CakeDc-User' => 'user',
        ]);
    }

    /**
     * @param array $roles realm roles
     * @return array
     */
    protected function rawData(array $roles = ['offline_access', 'CakeDc-User']): array
    {
        return [
            'token' => new AccessToken([
                'access_token' => 'test-token',
                'expires' => 1490988496,
            ]),
            'sub' => 'f4b8c1a2',
            'preferred_username' => 'test',
            'name' => 'Test User',
            'given_name' => 'Test',
            'family_name' => 'User',
            'email' => 'test@example.com',
            'email_verified' => false,
            'website' => 'https://example.com',
            'realm_access' => ['roles' => $roles],
        ];
    }

    /**
     * @return void
     */
    public function testMap(): void
    {
        $rawData = $this->rawData();
        $user = (new Keycloak())($rawData);

        $this->assertEquals([
            'id' => 'f4b8c1a2',
            'username' => 'test',
            'full_name' => 'Test User',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'avatar' => null,
            'gender' => null,
            'link' => 'https://example.com',
            'bio' => null,
            'locale' => null,
            'validated' => false,
            'roles' => 'user',
            'credentials' => [
                'token' => 'test-token',
                'secret' => null,
                'expires' => 1490988496,
            ],
            'raw' => $rawData,
        ], $user);
    }

    /**
     * The first matching rolesMap entry wins, roles are compared case-sensitive
     *
     * @return void
     */
    public function testRolesMapOrder(): void
    {
        $user = (new Keycloak())($this->rawData(['CakeDc-User', 'CakeDc-Admin']));
        $this->assertSame('admin', $user['roles']);

        $user = (new Keycloak())($this->rawData(['cakedc-admin', 'CakeDc-User']));
        $this->assertSame('user', $user['roles']);
    }

    /**
     * Without a roles map the role is not mapped and the login is not blocked
     *
     * @return void
     */
    public function testNoRolesMap(): void
    {
        Configure::delete('OAuth.providers.keycloak.rolesMap');
        $rawData = $this->rawData(['offline_access']);
        unset($rawData['realm_access']);

        $this->assertNull((new Keycloak())($rawData)['roles']);
    }

    /**
     * @return void
     */
    public function testRolesMapFromConfig(): void
    {
        Configure::write('OAuth.providers.keycloak.rolesMap', ['Manager' => 'superuser']);
        $user = (new Keycloak())($this->rawData(['Manager']));

        $this->assertSame('superuser', $user['roles']);
        $this->assertSame('user', (new Keycloak(null, ['CakeDc-User' => 'user']))($this->rawData())['roles']);
    }

    /**
     * @return void
     */
    public function testNoMappedRole(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('No mapped role found in Keycloak roles');

        (new Keycloak())($this->rawData(['offline_access']));
    }

    /**
     * @return void
     */
    public function testMissingRoles(): void
    {
        $rawData = $this->rawData();
        unset($rawData['realm_access']);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('No roles in Keycloak userinfo');

        (new Keycloak())($rawData);
    }
}
