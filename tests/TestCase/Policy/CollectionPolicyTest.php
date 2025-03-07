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
use Authorization\Policy\ResultInterface;
use Cake\Http\ServerRequestFactory;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use CakeDC\Auth\Policy\CollectionPolicy;
use CakeDC\Auth\Policy\RbacPolicy;
use CakeDC\Auth\Policy\Result\RbacResult;
use CakeDC\Auth\Policy\Result\SuperuserResult;
use CakeDC\Auth\Policy\SuperuserPolicy;
use CakeDC\Auth\Rbac\PermissionMatchResult;

/**
 * Class CollectionPolicyTest
 *
 * @package CakeDC\Auth\Test\TestCase\Policy
 */
class CollectionPolicyTest extends TestCase
{
    /**
     * Data provider for testCanAccess
     *
     * @return array
     */
    public function dataProviderCanAccess()
    {
        $rbacPolicy = function ($success) {
            $Mock = $this->getMockBuilder(RbacPolicy::class)
                ->onlyMethods(['canAccess'])
                ->getMock();
            $matchResult = new PermissionMatchResult(
                $success,
                'Some Reason',
                [
                    'prefix' => null,
                    'plugin' => null,
                    'extension' => null,
                    'controller' => 'Users',
                    'action' => 'index',
                    'role' => 'user',
                ],
                [
                    'role' => 'user',
                    'prefix' => false,
                    'plugin' => false,
                    'controller' => 'Users',
                    'action' => ['index', 'view'],
                    'allowed' => $success,
                ],
            );
            $result = new RbacResult($matchResult);
            $Mock->expects($this->once())
                ->method('canAccess')
                ->willReturn($result);

            return $Mock;
        };
        $rbacPolicyNever = function () {
            $Mock = $this->getMockBuilder(RbacPolicy::class)
                ->onlyMethods(['canAccess'])
                ->getMock();

            $Mock->expects($this->never())
                ->method('canAccess');

            return $Mock;
        };

        return [
            [true, $rbacPolicyNever(), true],
            [false, $rbacPolicy(false), false],
            [false, $rbacPolicy(true), true],
        ];
    }

    /**
     * Test canAccess method
     *
     * @param bool $isSuperuser Is this a super user
     * @param RbacPolicy $rbacPolicy Rbac policy instance
     * @param bool $expected The expected result;
     * @dataProvider dataProviderCanAccess
     * @return void
     */
    public function testCanAccess($isSuperuser, RbacPolicy $rbacPolicy, $expected)
    {
        $user = new Entity([
            'id' => '00000000-0000-0000-0000-000000000001',
            'is_superuser' => $isSuperuser,
        ]);
        $service = $this->createMock(AuthorizationServiceInterface::class);
        $identity = new IdentityDecorator($service, $user);
        $request = ServerRequestFactory::fromGlobals();

        $policy = new CollectionPolicy([
            SuperuserPolicy::class,
            $rbacPolicy,
        ]);

        $actual = $policy->canAccess($identity, $request);
        $this->assertInstanceOf(ResultInterface::class, $actual);
        if ($isSuperuser) {
            $this->assertInstanceOf(SuperuserResult::class, $actual);
        } else {
            $this->assertInstanceOf(RbacResult::class, $actual);
        }
        $this->assertSame($expected, $actual->getStatus());
    }
}
