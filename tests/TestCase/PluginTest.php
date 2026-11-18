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

namespace CakeDC\Auth\Test\TestCase;

use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\Routing\Exception\MissingRouteException;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use CakeDC\Auth\AuthPlugin;
use CakeDC\Auth\Plugin;
use PHPUnit\Framework\Attributes\DataProvider;

class PluginTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Router::reload();
        Configure::write('OAuth.path', [
            'plugin' => 'CakeDC/Users',
            'controller' => 'Users',
            'action' => 'socialLogin',
            'prefix' => null,
        ]);
        Configure::write('OAuth.providers', ['facebook' => [], 'keycloak' => []]);
    }

    /**
     * @return array<string, array{0: class-string<\Cake\Core\BasePlugin>}>
     */
    public static function pluginProvider(): array
    {
        return [
            'AuthPlugin' => [AuthPlugin::class],
            'Plugin' => [Plugin::class],
        ];
    }

    /**
     * Load the plugin routes
     *
     * @param class-string<\Cake\Core\BasePlugin> $pluginClass plugin class
     * @return void
     */
    protected function connectPluginRoutes(string $pluginClass): void
    {
        /** @var \Cake\Core\BasePlugin $plugin */
        $plugin = new $pluginClass();
        $this->assertInstanceOf(BasePlugin::class, $plugin);
        $plugin->routes(Router::createRouteBuilder('/'));
    }

    /**
     * Test the social login route accepts the configured providers
     *
     * @param class-string<\Cake\Core\BasePlugin> $pluginClass plugin class
     * @return void
     */
    #[DataProvider('pluginProvider')]
    public function testSocialLoginRoute(string $pluginClass): void
    {
        $this->connectPluginRoutes($pluginClass);

        $params = Router::parseRequest(new ServerRequest(['url' => '/auth/keycloak']));

        $this->assertSame('CakeDC/Users', $params['plugin']);
        $this->assertSame('Users', $params['controller']);
        $this->assertSame('socialLogin', $params['action']);
        $this->assertSame('keycloak', $params['provider']);
    }

    /**
     * Test the social login route rejects providers that are not configured
     *
     * @param class-string<\Cake\Core\BasePlugin> $pluginClass plugin class
     * @return void
     */
    #[DataProvider('pluginProvider')]
    public function testSocialLoginRouteUnknownProvider(string $pluginClass): void
    {
        $this->connectPluginRoutes($pluginClass);

        $this->expectException(MissingRouteException::class);
        Router::parseRequest(new ServerRequest(['url' => '/auth/unknown']));
    }

    /**
     * Test no route is connected without an `OAuth.path` array
     *
     * @param class-string<\Cake\Core\BasePlugin> $pluginClass plugin class
     * @return void
     */
    #[DataProvider('pluginProvider')]
    public function testNoSocialLoginRouteWithoutPath(string $pluginClass): void
    {
        Configure::write('OAuth.path', null);
        $this->connectPluginRoutes($pluginClass);

        $this->assertSame([], Router::routes());
    }
}
