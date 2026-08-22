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
use Cake\Http\ServerRequestFactory;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use CakeDC\Auth\Policy\CollectionPolicy;
use CakeDC\Auth\Policy\RbacPolicy;
use CakeDC\Auth\Policy\SuperuserPolicy;
use PHPUnit\Framework\Attributes\DataProvider;

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
    public static function dataProviderCanAccess()
    {
        return [
            [true, 'never', true],
            [false, 'false', false],
            [false, 'true', true],
        ];
    }

    /**
     * Test canAccess method
     *
     * @param bool $isSuperuser Is this a super user
     * @param string $rbacBehavior 'never', 'true', or 'false'
     * @param bool $expected The expected result;
     * @dataProvider dataProviderCanAccess
     * @return void
     */
    #[DataProvider('dataProviderCanAccess')]
    public function testCanAccess($isSuperuser, $rbacBehavior, $expected)
    {
        $user = new Entity([
            'id' => '00000000-0000-0000-0000-000000000001',
            'is_superuser' => $isSuperuser,
        ]);
        $service = $this->createStub(AuthorizationServiceInterface::class);
        $identity = new IdentityDecorator($service, $user);
        $request = ServerRequestFactory::fromGlobals();

        $rbacPolicy = $this->createMock(RbacPolicy::class);
        if ($rbacBehavior === 'never') {
            $rbacPolicy->expects($this->never())
                ->method('canAccess');
        } else {
            $rbacPolicy->expects($this->once())
                ->method('canAccess')
                ->willReturn($rbacBehavior === 'true');
        }

        $policy = new CollectionPolicy([
            SuperuserPolicy::class,
            $rbacPolicy,
        ]);

        $actual = $policy->canAccess($identity, $request);
        $this->assertSame($expected, $actual);
    }
}
