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

namespace CakeDC\Auth\Test\TestCase\Social\Service;

use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\ServerRequestFactory;
use Cake\TestSuite\TestCase;
use CakeDC\Auth\Social\Mapper\Facebook as FacebookMapper;
use CakeDC\Auth\Social\Mapper\Twitter as TwitterMapper;
use CakeDC\Auth\Social\Service\OAuth1Service;
use CakeDC\Auth\Social\Service\OAuth2Service;
use CakeDC\Auth\Social\Service\ServiceFactory;
use League\OAuth1\Client\Server\Twitter;
use League\OAuth2\Client\Provider\Facebook;

class ServiceFactoryTest extends TestCase
{
    /**
     * @var ServiceFactory
     */
    public $Factory;

    /**
     * Setup the test case, backup the static object values so they can be restored.
     * Specifically backs up the contents of Configure and paths in App if they have
     * not already been backed up.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->Factory = new ServiceFactory();
    }

    /**
     * Test createFromRequest method
     *
     * @return void
     */
    public function testCreateFromRequest()
    {
        $config = [
            'service' => OAuth2Service::class,
            'className' => Facebook::class,
            'mapper' => FacebookMapper::class,
            'authParams' => ['scope' => ['public_profile', 'email', 'user_birthday', 'user_gender', 'user_link']],
            'options' => [
                'state' => '__TEST_STATE__',
                'graphApiVersion' => 'v2.8',
                'redirectUri' => '/auth/facebook',
                'linkSocialUri' => '/link-social/facebook',
                'callbackLinkSocialUri' => '/callback-link-social/facebook',
                'clientId' => '10003030300303',
                'clientSecret' => 'secretpassword',
            ],
            'collaborators' => [],
            'signature' => null,
            'mapFields' => [],
            'path' => [
                'plugin' => 'CakeDC/Users',
                'controller' => 'Users',
                'action' => 'socialLogin',
                'prefix' => null,
            ],
        ];
        Configure::write('OAuth.providers.facebook', $config);

        $request = ServerRequestFactory::fromGlobals();
        $request = $request->withQueryParams([
            'code' => 'ZPO9972j3092304230',
            'state' => '__TEST_STATE__',
        ]);
        $request = $request->withAttribute('params', [
            'plugin' => 'CakeDC/Users',
            'controller' => 'Users',
            'action' => 'socialLogin',
            'provider' => 'facebook',
        ]);

        $service = $this->Factory->createFromRequest($request);
        $this->assertInstanceOf(OAuth2Service::class, $service);
        $this->assertEquals('facebook', $service->getProviderName());

        $expected = [
            'service' => OAuth2Service::class,
            'className' => Facebook::class,
            'mapper' => FacebookMapper::class,
            'authParams' => ['scope' => ['public_profile', 'email', 'user_birthday', 'user_gender', 'user_link']],
            'options' => [
                'state' => '__TEST_STATE__',
                'graphApiVersion' => 'v2.8',
                'redirectUri' => '/auth/facebook',
                'clientId' => '10003030300303',
                'clientSecret' => 'secretpassword',
            ],
            'collaborators' => [],
            'signature' => null,
            'mapFields' => [],
            'path' => [
                'plugin' => 'CakeDC/Users',
                'controller' => 'Users',
                'action' => 'socialLogin',
                'prefix' => null,
            ],
        ];
        $actual = $service->getConfig();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test createFromRequest method
     *
     * @return void
     */
    public function testCreateFromRequestCustomRedirectUriField()
    {
        $config = [
            'service' => OAuth2Service::class,
            'className' => Facebook::class,
            'mapper' => FacebookMapper::class,
            'authParams' => ['scope' => ['public_profile', 'email', 'user_birthday', 'user_gender', 'user_link']],
            'options' => [
                'state' => '__TEST_STATE__',
                'graphApiVersion' => 'v2.8',
                'redirectUri' => '/auth/facebook',
                'linkSocialUri' => '/link-social/facebook',
                'callbackLinkSocialUri' => '/callback-link-social/facebook',
                'clientId' => '10003030300303',
                'clientSecret' => 'secretpassword',
            ],
            'collaborators' => [],
            'signature' => null,
            'mapFields' => [],
            'path' => [
                'plugin' => 'CakeDC/Users',
                'controller' => 'Users',
                'action' => 'socialLogin',
                'prefix' => null,
            ],
        ];
        Configure::write('OAuth.providers.facebook', $config);

        $request = ServerRequestFactory::fromGlobals();
        $request = $request->withQueryParams([
            'code' => 'ZPO9972j3092304230',
            'state' => '__TEST_STATE__',
        ]);
        $request = $request->withAttribute('params', [
            'plugin' => 'CakeDC/Users',
            'controller' => 'Users',
            'action' => 'socialLogin',
            'provider' => 'facebook',
        ]);

        $this->Factory->setRedirectUriField('callbackLinkSocialUri');
        $service = $this->Factory->createFromRequest($request);
        $this->assertInstanceOf(OAuth2Service::class, $service);
        $this->assertEquals('facebook', $service->getProviderName());

        $expected = [
            'service' => OAuth2Service::class,
            'className' => Facebook::class,
            'mapper' => FacebookMapper::class,
            'authParams' => ['scope' => ['public_profile', 'email', 'user_birthday', 'user_gender', 'user_link']],
            'options' => [
                'state' => '__TEST_STATE__',
                'graphApiVersion' => 'v2.8',
                'redirectUri' => '/callback-link-social/facebook',
                'clientId' => '10003030300303',
                'clientSecret' => 'secretpassword',
            ],
            'collaborators' => [],
            'signature' => null,
            'mapFields' => [],
            'path' => [
                'plugin' => 'CakeDC/Users',
                'controller' => 'Users',
                'action' => 'socialLogin',
                'prefix' => null,
            ],
        ];
        $actual = $service->getConfig();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test createFromRequest method, with oauth1
     *
     * @return void
     */
    public function testCreateFromRequestOAuth1()
    {
        $config = [
            'service' => OAuth1Service::class,
            'className' => Twitter::class,
            'mapper' => TwitterMapper::class,
            'authParams' => ['scope' => ['public_profile', 'email', 'user_birthday', 'user_gender', 'user_link']],
            'options' => [
                'redirectUri' => '/auth/twitter',
                'linkSocialUri' => '/link-social/twitter',
                'callbackLinkSocialUri' => '/callback-link-social/twitter',
                'clientId' => '20003030300303',
                'clientSecret' => 'weakpassword',
            ],
            'collaborators' => [],
            'signature' => null,
            'mapFields' => [],
            'path' => [
                'plugin' => 'CakeDC/Users',
                'controller' => 'Users',
                'action' => 'socialLogin',
                'prefix' => null,
            ],
        ];
        Configure::write('OAuth.providers.twitter', $config);

        $request = ServerRequestFactory::fromGlobals();
        $request = $request->withAttribute('params', [
            'plugin' => 'CakeDC/Users',
            'controller' => 'Users',
            'action' => 'socialLogin',
            'provider' => 'twitter',
        ]);

        $actual = $this->Factory->createFromRequest($request);
        $this->assertInstanceOf(OAuth1Service::class, $actual);
        $this->assertEquals('twitter', $actual->getProviderName());
    }

    /**
     * Test createFromRequest method, provider not enabled
     *
     * @return void
     */
    public function testCreateFromRequestProviderNotEnabled()
    {
        $request = ServerRequestFactory::fromGlobals();
        $request = $request->withQueryParams([
            'code' => 'ZPO9972j3092304230',
            'state' => '__TEST_STATE__',
        ]);
        $request = $request->withAttribute('params', [
            'plugin' => 'CakeDC/Users',
            'controller' => 'Users',
            'action' => 'socialLogin',
            'provider' => 'facebook',
        ]);

        Configure::delete('OAuth.providers.facebook.options.redirectUri');
        Configure::delete('OAuth.providers.facebook.options.linkSocialUri');
        Configure::delete('OAuth.providers.facebook.options.callbackLinkSocialUri');
        Configure::write('OAuth.providers.facebook', []);

        $this->expectException(NotFoundException::class);
        $this->Factory->createFromRequest($request);
    }
}
