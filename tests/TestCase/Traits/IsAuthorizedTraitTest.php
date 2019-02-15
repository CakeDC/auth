<?php
/**
 * Copyright 2010 - 2019, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010 - 2018, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace CakeDC\Auth\Test\TestCase\Traits;

use Authentication\Identity;
use Authorization\AuthorizationService;
use Authorization\IdentityDecorator;
use Authorization\Policy\MapResolver;
use Authorization\Policy\OrmResolver;
use Authorization\Policy\ResolverCollection;
use CakeDC\Auth\Policy\CollectionPolicy;
use CakeDC\Auth\Policy\RbacPolicy;
use CakeDC\Auth\Policy\SuperuserPolicy;
use CakeDC\Auth\Rbac\Rbac;
use CakeDC\Auth\Traits\IsAuthorizedTrait;
use Cake\Http\ServerRequest;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;

/**
 * Class IsAuthorizedTraitTest
 *
 * @package CakeDC\Auth\Test\TestCase\Traits
 */
class IsAuthorizedTraitTest extends TestCase
{
    public function dataProviderIsAuthorized()
    {
        return [
            [true],
            [false]
        ];
    }

    /**
     * Test link
     *
     * @param bool $authorize Is authorized?
     *
     * @dataProvider dataProviderIsAuthorized
     * @return void
     */
    public function testIsAuthorizedWithMock($authorize)
    {
        $user = new Entity([
            'id' => '00000000-0000-0000-0000-000000000001',
            'password' => '12345'
        ]);
        $identity = new Identity($user);
        $request = new ServerRequest();
        $rbac = $this->getMockBuilder(Rbac::class)->setMethods(['checkPermissions'])->getMock();
        $rbac->expects($this->once())
            ->method('checkPermissions')
            ->with(
                $this->equalTo($identity->getOriginalData()->toArray())
            )
            ->will($this->returnValue($authorize));
        $request = $request->withAttribute('rbac', $rbac);

        $map = new MapResolver();
        $map->map(
            ServerRequest::class,
            new CollectionPolicy([
                SuperuserPolicy::class,
                RbacPolicy::class
            ])
        );
        $orm = new OrmResolver();
        $resolver = new ResolverCollection([
            $map,
            $orm
        ]);
        $service = new AuthorizationService($resolver);
        $request = $request->withAttribute('authorization', $service);
        $request = $request->withAttribute('identity', new IdentityDecorator($service, $identity));

        $Trait = $this->getMockBuilder('\CakeDC\Auth\Traits\IsAuthorizedTrait')
            ->setMethods(['getRequest'])
            ->getMockForTrait();
        $Trait->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($request));

        $result = $Trait->isAuthorized([
            'plugin' => 'CakeDC/Users',
            'controller' => 'Users',
            'action' => 'myTest'
        ]);
        $this->assertSame($authorize, $result);
    }

    /**
     * Test isAuthorized
     *
     * @return void
     */
    public function tedsdtIsAuthorizedAuthorizedHappy()
    {
        $user = new User([
            'id' => '00000000-0000-0000-0000-000000000001',
            'password' => '12345'
        ]);
        $identity = new Identity($user);
        $this->AuthLink->getView()->setRequest($this->AuthLink->getView()->getRequest()->withAttribute('identity', $identity));
        $rbac = $this->getMockBuilder(Rbac::class)->setMethods(['checkPermissions'])->getMock();
        $rbac->expects($this->once())
            ->method('checkPermissions')
            ->with($identity->getOriginalData()->toArray())
            ->will($this->returnValue(true));
        $this->AuthLink->getView()->setRequest($this->AuthLink->getView()->getRequest()->withAttribute('rbac', $rbac));
        $link = $this->AuthLink->link(
            'title',
            ['plugin' => 'CakeDC/Users', 'controller' => 'Users', 'action' => 'profile'],
            ['before' => 'before_', 'after' => '_after', 'class' => 'link-class']
        );
        $this->assertSame('before_<a href="/profile" class="link-class">title</a>_after', $link);
    }
}
