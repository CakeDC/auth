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
namespace CakeDC\Auth\Test\TestCase\Authenticator;

use Authentication\Authenticator\FormAuthenticator as CakeFormAuthenticator;
use Authentication\Authenticator\Result;
use Authentication\Identifier\AbstractIdentifier;
use Authentication\Identifier\IdentifierCollection;
use Cake\Core\Configure;
use Cake\Http\ServerRequestFactory;
use Cake\TestSuite\TestCase;
use CakeDC\Auth\Authenticator\FormAuthenticator;
use InvalidArgumentException;

class FormAuthenticatorTest extends TestCase
{
    /**
     * testAuthenticate
     *
     * @return void
     */
    public function testAuthenticateBaseFailed()
    {
        $identifiers = new IdentifierCollection([
            'Authentication.Password',
        ]);

        $BaseAuthenticator = $this->getMockBuilder(CakeFormAuthenticator::class)
            ->setConstructorArgs([$identifiers])
            ->onlyMethods(['authenticate'])
            ->getMock();
        $request = ServerRequestFactory::fromGlobals(
            ['REQUEST_URI' => '/testpath'],
            [],
            ['username' => 'marcelo', 'password' => 'password', 'g-recaptcha-response' => 'BD-S2333-156465897897'],
        );

        $baseResult = new Result(
            null,
            Result::FAILURE_OTHER,
        );
        $BaseAuthenticator->expects($this->once())
            ->method('authenticate')
            ->with($request)
            ->willReturn($baseResult);

        $Authenticator = $this->getMockBuilder(FormAuthenticator::class)->setConstructorArgs([
            $identifiers,
            [
                'fields' => [
                    AbstractIdentifier::CREDENTIAL_USERNAME => 'email',
                    AbstractIdentifier::CREDENTIAL_PASSWORD => 'password',
                ],
                'keyCheckEnabledRecaptcha' => 'Users.reCaptcha.login',
            ],
        ])->onlyMethods(['createBaseAuthenticator', 'validateReCaptcha'])->getMock();

        Configure::write('Users.reCaptcha.login', true);
        $Authenticator->expects($this->once())
            ->method('createBaseAuthenticator')
            ->with(
                $this->equalTo($identifiers),
                $this->equalTo([
                    'fields' => [
                        AbstractIdentifier::CREDENTIAL_USERNAME => 'email',
                        AbstractIdentifier::CREDENTIAL_PASSWORD => 'password',
                    ],
                    'keyCheckEnabledRecaptcha' => 'Users.reCaptcha.login',
                ]),
            )->willReturn($BaseAuthenticator);

        $Authenticator->expects($this->never())
            ->method('validateReCaptcha');

        $result = $Authenticator->authenticate($request);
        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::FAILURE_OTHER, $result->getStatus());
        $this->assertSame($baseResult, $result);
    }

    /**
     * testAuthenticate
     *
     * @return void
     */
    public function testAuthenticate()
    {
        $identifiers = new IdentifierCollection([
            'Authentication.Password',
        ]);

        $BaseAuthenticator = $this->getMockBuilder(CakeFormAuthenticator::class)
            ->setConstructorArgs([$identifiers])
            ->onlyMethods(['authenticate'])
            ->getMock();
        $request = ServerRequestFactory::fromGlobals(
            ['REQUEST_URI' => '/testpath'],
            [],
            ['username' => 'marcelo', 'password' => 'password', 'g-recaptcha-response' => 'BD-S2333-156465897897'],
        );

        $baseResult = new Result(
            [
                'id' => '42',
                'username' => 'marcelo',
                'role' => 'user',
            ],
            Result::SUCCESS,
        );
        $BaseAuthenticator->expects($this->once())
            ->method('authenticate')
            ->with($request)
            ->willReturn($baseResult);

        $Authenticator = $this->getMockBuilder(FormAuthenticator::class)->setConstructorArgs([
            $identifiers,
            [
                'fields' => [
                    AbstractIdentifier::CREDENTIAL_USERNAME => 'email',
                    AbstractIdentifier::CREDENTIAL_PASSWORD => 'password',
                ],
                'keyCheckEnabledRecaptcha' => 'Users.reCaptcha.login',
            ],
        ])->onlyMethods(['createBaseAuthenticator', 'validateReCaptcha'])->getMock();

        Configure::write('Users.reCaptcha.login', true);
        $Authenticator->expects($this->once())
            ->method('createBaseAuthenticator')
            ->with(
                $this->equalTo($identifiers),
                $this->equalTo([
                    'fields' => [
                        AbstractIdentifier::CREDENTIAL_USERNAME => 'email',
                        AbstractIdentifier::CREDENTIAL_PASSWORD => 'password',
                    ],
                    'keyCheckEnabledRecaptcha' => 'Users.reCaptcha.login',
                ]),
            )->willReturn($BaseAuthenticator);

        $Authenticator->expects($this->once())
            ->method('validateReCaptcha')
            ->with(
                $this->equalTo('BD-S2333-156465897897'),
            )
            ->willReturn(true);
        $actualIdentifiers = $Authenticator->getIdentifier();
        $this->assertInstanceOf(IdentifierCollection::class, $actualIdentifiers);
        $result = $Authenticator->authenticate($request);
        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::SUCCESS, $result->getStatus());
        $this->assertSame($baseResult, $result);
    }

    /**
     * testAuthenticate
     *
     * @return void
     */
    public function testAuthenticateNotRequiredReCaptcha()
    {
        $identifiers = new IdentifierCollection([
            'Authentication.Password',
        ]);

        $BaseAuthenticator = $this->getMockBuilder(CakeFormAuthenticator::class)
            ->setConstructorArgs([$identifiers])
            ->onlyMethods(['authenticate'])
            ->getMock();
        $request = ServerRequestFactory::fromGlobals(
            ['REQUEST_URI' => '/testpath'],
            [],
            ['username' => 'marcelo', 'password' => 'password', 'g-recaptcha-response' => 'BD-S2333-156465897897'],
        );

        $baseResult = new Result(
            [
                'id' => '42',
                'username' => 'marcelo',
                'role' => 'user',
            ],
            Result::SUCCESS,
        );
        $BaseAuthenticator->expects($this->once())
            ->method('authenticate')
            ->with($request)
            ->willReturn($baseResult);

        $Authenticator = $this->getMockBuilder(FormAuthenticator::class)->setConstructorArgs([
            $identifiers,
            [
                'fields' => [
                    AbstractIdentifier::CREDENTIAL_USERNAME => 'email',
                    AbstractIdentifier::CREDENTIAL_PASSWORD => 'password',
                ],
            ],
        ])->onlyMethods(['createBaseAuthenticator', 'validateReCaptcha'])->getMock();

        Configure::write('Users.reCaptcha.login', false);
        $Authenticator->expects($this->once())
            ->method('createBaseAuthenticator')
            ->with(
                $this->equalTo($identifiers),
                $this->equalTo([
                    'fields' => [
                        AbstractIdentifier::CREDENTIAL_USERNAME => 'email',
                        AbstractIdentifier::CREDENTIAL_PASSWORD => 'password',
                    ],
                    'keyCheckEnabledRecaptcha' => 'Users.reCaptcha.login',
                ]),
            )->willReturn($BaseAuthenticator);

        $Authenticator->expects($this->never())
            ->method('validateReCaptcha');

        $result = $Authenticator->authenticate($request);
        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::SUCCESS, $result->getStatus());
        $this->assertSame($baseResult, $result);
    }

    /**
     * testAuthenticate
     *
     * @return void
     */
    public function testAuthenticateInvalidRecaptcha()
    {
        $identifiers = new IdentifierCollection([
            'Authentication.Password',
        ]);

        $BaseAuthenticator = $this->getMockBuilder(CakeFormAuthenticator::class)
            ->setConstructorArgs([$identifiers])
            ->onlyMethods(['authenticate'])
            ->getMock();
        $request = ServerRequestFactory::fromGlobals(
            ['REQUEST_URI' => '/testpath'],
            [],
            ['username' => 'marcelo', 'password' => 'password', 'g-recaptcha-response' => 'BD-S2333-156465897897'],
        );

        $baseResult = new Result(
            [
                'id' => '42',
                'username' => 'marcelo',
                'role' => 'user',
            ],
            Result::SUCCESS,
        );
        $BaseAuthenticator->expects($this->once())
            ->method('authenticate')
            ->with($request)
            ->willReturn($baseResult);

        $Authenticator = $this->getMockBuilder(FormAuthenticator::class)->setConstructorArgs([
            $identifiers,
            [
                'fields' => [
                    AbstractIdentifier::CREDENTIAL_USERNAME => 'email',
                    AbstractIdentifier::CREDENTIAL_PASSWORD => 'password',
                ],
            ],
        ])->onlyMethods(['createBaseAuthenticator', 'validateReCaptcha'])->getMock();

        Configure::write('Users.reCaptcha.login', true);
        $Authenticator->expects($this->once())
            ->method('createBaseAuthenticator')
            ->with(
                $this->equalTo($identifiers),
                $this->equalTo([
                    'fields' => [
                        AbstractIdentifier::CREDENTIAL_USERNAME => 'email',
                        AbstractIdentifier::CREDENTIAL_PASSWORD => 'password',
                    ],
                    'keyCheckEnabledRecaptcha' => 'Users.reCaptcha.login',
                ]),
            )->willReturn($BaseAuthenticator);

        $Authenticator->expects($this->once())
            ->method('validateReCaptcha')
            ->with(
                $this->equalTo('BD-S2333-156465897897'),
            )
            ->willReturn(false);

        $result = $Authenticator->authenticate($request);
        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(FormAuthenticator::FAILURE_INVALID_RECAPTCHA, $result->getStatus());
        $this->assertNull($result->getData());
    }

    /**
     * A request without a g-recaptcha-response token, with reCaptcha enabled and valid
     * credentials, must fail as FAILURE_INVALID_RECAPTCHA through the real
     * validateReCaptchaFromRequest() guard (no token never reaches validateReCaptcha(),
     * so this asserts the guard end-to-end and that it no longer raises a TypeError).
     *
     * @return void
     */
    public function testAuthenticateTokenlessRecaptchaFailsCleanly()
    {
        $identifiers = new IdentifierCollection([
            'Authentication.Password',
        ]);

        $BaseAuthenticator = $this->getMockBuilder(CakeFormAuthenticator::class)
            ->setConstructorArgs([$identifiers])
            ->onlyMethods(['authenticate'])
            ->getMock();
        $request = ServerRequestFactory::fromGlobals(
            ['REQUEST_URI' => '/testpath', 'REMOTE_ADDR' => '127.0.0.1'],
            [],
            ['username' => 'marcelo', 'password' => 'password']
        );

        $baseResult = new Result(
            [
                'id' => '42',
                'username' => 'marcelo',
                'role' => 'user',
            ],
            Result::SUCCESS
        );
        $BaseAuthenticator->expects($this->once())
            ->method('authenticate')
            ->with($request)
            ->willReturn($baseResult);

        // Only the base authenticator is mocked; validateReCaptcha() is left real and
        // must never be reached because the token is absent.
        $Authenticator = $this->getMockBuilder(FormAuthenticator::class)->setConstructorArgs([
            $identifiers,
            [
                'fields' => [
                    AbstractIdentifier::CREDENTIAL_USERNAME => 'email',
                    AbstractIdentifier::CREDENTIAL_PASSWORD => 'password',
                ],
            ],
        ])->onlyMethods(['createBaseAuthenticator'])->getMock();

        Configure::write('Users.reCaptcha.login', true);
        $Authenticator->expects($this->once())
            ->method('createBaseAuthenticator')
            ->willReturn($BaseAuthenticator);

        $result = $Authenticator->authenticate($request);
        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(FormAuthenticator::FAILURE_INVALID_RECAPTCHA, $result->getStatus());
        $this->assertNull($result->getData());
    }

    /**
     * test getBaseAuthenticator
     *
     * @return void
     */
    public function testGetBaseAuthenticator()
    {
        $identifiers = new IdentifierCollection([
            'Authentication.Password',
        ]);

        $Authenticator = new FormAuthenticator(
            $identifiers,
            [
                'fields' => [
                    AbstractIdentifier::CREDENTIAL_USERNAME => 'email',
                    AbstractIdentifier::CREDENTIAL_PASSWORD => 'password',
                ],
                'keyCheckEnabledRecaptcha' => 'Users.reCaptcha.login',
            ],
        );
        $actual = $Authenticator->getBaseAuthenticator();
        $this->assertInstanceOf(CakeFormAuthenticator::class, $actual);
        $this->assertNotInstanceOf(FormAuthenticator::class, $actual);
        $expected = [
            'fields' => [
                AbstractIdentifier::CREDENTIAL_USERNAME => 'email',
                AbstractIdentifier::CREDENTIAL_PASSWORD => 'password',
            ],
            'loginUrl' => null,
            'urlChecker' => 'Authentication.Default',
        ];
        $this->assertEquals($expected, $actual->getConfig());
    }

    /**
     * test getBaseAuthenticator
     *
     * @return void
     */
    public function testGetBaseAuthenticatorCustom()
    {
        $identifiers = new IdentifierCollection([
            'Authentication.Password',
        ]);

        $Authenticator = new FormAuthenticator(
            $identifiers,
            [
                'fields' => [
                    AbstractIdentifier::CREDENTIAL_USERNAME => 'email',
                    AbstractIdentifier::CREDENTIAL_PASSWORD => 'password',
                ],
                'keyCheckEnabledRecaptcha' => 'Users.reCaptcha.login',
                'baseClassName' => CakeFormAuthenticator::class,
            ],
        );
        $actual = $Authenticator->getBaseAuthenticator();
        $this->assertInstanceOf(CakeFormAuthenticator::class, $actual);
        $this->assertNotInstanceOf(FormAuthenticator::class, $actual);
        $expected = [
            'fields' => [
                AbstractIdentifier::CREDENTIAL_USERNAME => 'email',
                AbstractIdentifier::CREDENTIAL_PASSWORD => 'password',
            ],
            'loginUrl' => null,
            'urlChecker' => 'Authentication.Default',
        ];
        $this->assertEquals($expected, $actual->getConfig());
    }

    /**
     * test getBaseAuthenticator
     *
     * @return void
     */
    public function testGetBaseAuthenticatorError()
    {
        $identifiers = new IdentifierCollection([
            'Authentication.Password',
        ]);

        $Authenticator = new FormAuthenticator(
            $identifiers,
            [
                'fields' => [
                    AbstractIdentifier::CREDENTIAL_USERNAME => 'email',
                    AbstractIdentifier::CREDENTIAL_PASSWORD => 'password',
                ],
                'keyCheckEnabledRecaptcha' => 'Users.reCaptcha.login',
                'baseClassName' => 'NotExistingAuthenticator',
            ],
        );
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Base class for FormAuthenticator NotExistingAuthenticator does not exist');
        $Authenticator->getBaseAuthenticator();
    }
}
