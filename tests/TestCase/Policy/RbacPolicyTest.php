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

namespace CakeDC\Auth\Test\TestCase\Policy;

use Authentication\Identity;
use CakeDC\Auth\Policy\RbacPolicy;
use CakeDC\Auth\Rbac\Rbac;
use Cake\Http\ServerRequestFactory;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;

class RbacPolicyTest extends TestCase
{
    /**
     * Test before method, with rbac returning true
     */
    public function testBeforeRbacReturnedTrue()
    {
        $user = new Entity([
            'id' => '00000000-0000-0000-0000-000000000001',
            'password' => '12345'
        ]);
        $identity = new Identity($user);
        $request = ServerRequestFactory::fromGlobals();
        $request = $request->withAttribute('identity', $identity);
        $rbac = $this->getMockBuilder(Rbac::class)->setMethods(['checkPermissions'])->getMock();
        $request = $request->withAttribute('rbac', $rbac);
        $rbac->expects($this->once())
            ->method('checkPermissions')
            ->with(
                $this->equalTo($identity->getOriginalData()->toArray()),
                $this->equalTo($request)
            )
            ->will($this->returnValue(true));
        $policy = new RbacPolicy();
        $this->assertTrue($policy->canAccess($identity, $request));
    }

    /**
     * Test before method, with rbac returning false
     */
    public function testBeforeRbacReturnedFalse()
    {
        $user = new Entity([
            'id' => '00000000-0000-0000-0000-000000000001',
            'password' => '12345'
        ]);
        $identity = new Identity($user);
        $request = ServerRequestFactory::fromGlobals();
        $request = $request->withAttribute('identity', $identity);
        $rbac = $this->getMockBuilder(Rbac::class)->setMethods(['checkPermissions'])->getMock();
        $request = $request->withAttribute('rbac', $rbac);
        $rbac->expects($this->once())
            ->method('checkPermissions')
            ->with(
                $this->equalTo($identity->getOriginalData()->toArray()),
                $this->equalTo($request)
            )
            ->will($this->returnValue(false));
        $request = $request->withAttribute('rbac', $rbac);
        $policy = new RbacPolicy();
        $this->assertFalse($policy->canAccess($identity, $request));
    }
}
