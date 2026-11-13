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

namespace CakeDC\Auth\Test\TestCase\Policy;

use Authorization\AuthorizationServiceInterface;
use Authorization\IdentityDecorator;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Event\EventManager;
use Cake\Http\ServerRequestFactory;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use CakeDC\Auth\Policy\SuperuserPolicy;
use PHPUnit\Framework\Attributes\DataProvider;

class SuperuserPolicyTest extends TestCase
{
    /**
     * Data provider for testCanAccess
     *
     * @return array
     */
    public static function dataProviderCanAccess()
    {
        return [
            [['is_superuser' => true], true],
            [['is_superuser' => false], false],
            [[], false],
            [['is_superuser' => true], false, ['superuser_field' => 'is_master']],
            [['is_master' => true], true, ['superuser_field' => 'is_master']],
            [['is_master' => false], false, ['superuser_field' => 'is_master']],
            [['is_superuser' => false], false, ['superuser_field' => 'is_master']],
            [[], false, ['superuser_field' => 'is_master']],
        ];
    }

    /**
     * Test canAccess method
     *
     * @param array $userData custom user data for testing.
     * @param bool $expected The expected result.
     * @param array $config policy configurations.
     * @return void
     */
    #[DataProvider('dataProviderCanAccess')]
    public function testCanAccess($userData, $expected, $config = [])
    {
        $user = new Entity($userData + [
            'id' => '00000000-0000-0000-0000-000000000001',
        ]);
        $service = $this->createStub(AuthorizationServiceInterface::class);
        $identity = new IdentityDecorator($service, $user);
        $request = ServerRequestFactory::fromGlobals();
        $request = $request->withAttribute('identity', $identity);

        $policy = new SuperuserPolicy($config);
        $actual = $policy->canAccess($request->getAttribute('identity'), $request);
        $this->assertSame($expected, $actual);
    }

    /**
     * @return array<string, array{0: bool, 1: bool, 2: bool}>
     */
    public static function checkedEventProvider(): array
    {
        return [
            'superuser entity, events enabled' => [true, true, true],
            'superuser array, events enabled' => [false, true, true],
            'superuser, events disabled' => [true, false, false],
        ];
    }

    /**
     * Test the `Auth.Authorization.checked` event for superusers
     *
     * @param bool $entity identity data is an entity
     * @param bool $emitEvents `CakeDC/Auth.emitEvents` value
     * @param bool $dispatched event expected
     * @return void
     */
    #[DataProvider('checkedEventProvider')]
    public function testCheckedEvent(bool $entity, bool $emitEvents, bool $dispatched): void
    {
        Configure::write('CakeDC/Auth.emitEvents', $emitEvents);
        $events = [];
        $listener = function (EventInterface $event) use (&$events): void {
            $events[] = $event;
        };
        EventManager::instance()->on('Auth.Authorization.checked', $listener);
        $userData = ['id' => 1, 'role' => 'admin', 'is_superuser' => true];
        $user = $entity ? new Entity($userData) : $userData;
        $identity = new IdentityDecorator($this->createStub(AuthorizationServiceInterface::class), $user);
        $request = ServerRequestFactory::fromGlobals();

        try {
            $this->assertTrue((new SuperuserPolicy())->canAccess($identity, $request));
        } finally {
            EventManager::instance()->off('Auth.Authorization.checked', $listener);
        }

        if (!$dispatched) {
            $this->assertSame([], $events);

            return;
        }
        $this->assertCount(1, $events);
        $this->assertSame([
            'allowed' => true,
            'policy' => SuperuserPolicy::class,
            'role' => 'admin',
            'user' => $user,
            'request' => $request,
            'permission' => null,
            'reason' => 'SuperuserPolicy allowed',
        ], $events[0]->getData());
    }

    /**
     * Test no event is dispatched when the user is not a superuser
     *
     * @return void
     */
    public function testCheckedEventNotSuperuser(): void
    {
        Configure::write('CakeDC/Auth.emitEvents', true);
        $events = [];
        $listener = function (EventInterface $event) use (&$events): void {
            $events[] = $event;
        };
        EventManager::instance()->on('Auth.Authorization.checked', $listener);
        $identity = new IdentityDecorator(
            $this->createStub(AuthorizationServiceInterface::class),
            new Entity(['id' => 1, 'is_superuser' => false]),
        );

        try {
            $this->assertFalse((new SuperuserPolicy())->canAccess($identity, ServerRequestFactory::fromGlobals()));
        } finally {
            EventManager::instance()->off('Auth.Authorization.checked', $listener);
        }
        $this->assertSame([], $events);
    }
}
