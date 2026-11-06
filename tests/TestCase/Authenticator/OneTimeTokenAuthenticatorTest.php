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

namespace CakeDC\Auth\Test\TestCase\Authenticator;

use Authentication\Authenticator\Result;
use Authentication\Identifier\IdentifierCollection;
use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use CakeDC\Auth\Authenticator\OneTimeTokenAuthenticator;
use PHPUnit\Framework\Attributes\DataProvider;

class OneTimeTokenAuthenticatorTest extends TestCase
{
    /**
     * Tokens received by the users table
     *
     * @var array<string>
     */
    public static array $receivedTokens = [];

    /**
     * @var \CakeDC\Auth\Authenticator\OneTimeTokenAuthenticator
     */
    protected OneTimeTokenAuthenticator $Authenticator;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        static::$receivedTokens = [];
        $table = new class (['alias' => 'TokenUsers', 'table' => 'users']) extends Table {
            /**
             * @param string $token one time token
             * @return \Cake\ORM\Entity|null
             */
            public function loginWithToken(string $token): ?Entity
            {
                OneTimeTokenAuthenticatorTest::$receivedTokens[] = $token;

                return $token === 'valid-token' ? new Entity(['id' => 1, 'username' => 'user-1']) : null;
            }
        };
        $this->getTableLocator()->set('TokenUsers', $table);
        Configure::write('Users.table', 'TokenUsers');
        $this->Authenticator = new OneTimeTokenAuthenticator(new IdentifierCollection());
    }

    /**
     * @return array<string, array{0: array, 1: array, 2: string, 3: array<string>}>
     */
    public static function authenticateProvider(): array
    {
        return [
            'valid query token' => [['token' => 'valid-token'], [], Result::SUCCESS, ['valid-token']],
            'valid post token' => [[], ['token' => 'valid-token'], Result::SUCCESS, ['valid-token']],
            'query token wins' => [
                ['token' => 'invalid-token'],
                ['token' => 'valid-token'],
                Result::FAILURE_CREDENTIALS_MISSING,
                ['invalid-token'],
            ],
            'invalid token' => [['token' => 'invalid-token'], [], Result::FAILURE_CREDENTIALS_MISSING, ['invalid-token']],
            'per character inputs are joined' => [
                [],
                ['token' => ['v', 'a', 'l', 'i', 'd', '-', 't', 'o', 'k', 'e', 'n']],
                Result::SUCCESS,
                ['valid-token'],
            ],
            'nested array token' => [['token' => ['valid-', ['token']]], [], Result::FAILURE_CREDENTIALS_MISSING, []],
            'no token' => [[], [], Result::FAILURE_CREDENTIALS_MISSING, []],
            'empty token' => [['token' => ''], ['token' => ''], Result::FAILURE_CREDENTIALS_MISSING, []],
        ];
    }

    /**
     * @param array $query query params
     * @param array $data post data
     * @param string $status expected result status
     * @param array<string> $lookups tokens passed to the users table
     * @return void
     */
    #[DataProvider('authenticateProvider')]
    public function testAuthenticate(array $query, array $data, string $status, array $lookups): void
    {
        $request = (new ServerRequest(['url' => '/login']))
            ->withQueryParams($query)
            ->withParsedBody($data);

        $result = $this->Authenticator->authenticate($request);

        $this->assertSame($status, $result->getStatus());
        $this->assertSame($lookups, static::$receivedTokens);
        if ($status === Result::SUCCESS) {
            $this->assertSame('user-1', $result->getData()['username']);
        } else {
            $this->assertNull($result->getData());
        }
    }
}
