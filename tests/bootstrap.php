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

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Error\ErrorTrap;
use Cake\Routing\Router;
use Cake\TestSuite\Fixture\SchemaLoader;
use Cake\Utility\Security;
use CakeDC\Auth\Test\TestApplication;

/**
 * Test suite bootstrap.
 *
 * This function is used to find the location of CakePHP whether CakePHP
 * has been installed as a dependency of the plugin, or the plugin is itself
 * installed as a dependency of an application.
 */
$findRoot = function ($root) {
    do {
        $lastRoot = $root;
        $root = dirname($root);
        if (is_dir($root . '/vendor/cakephp/cakephp')) {
            return $root;
        }
    } while ($root !== $lastRoot);
    throw new Exception('Cannot find the root of the application, unable to run tests');
};
$root = $findRoot(__FILE__);
unset($findRoot);
chdir($root);

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
define('ROOT', $root);
define('APP_DIR', 'App');

define('TMP', ROOT . DS . 'tmp' . DS);
define('LOGS', TMP . 'logs' . DS);
define('CACHE', TMP . 'cache' . DS);
define('SESSIONS', TMP . 'sessions' . DS);

define('CAKE_CORE_INCLUDE_PATH', ROOT . '/vendor/cakephp/cakephp');
define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
define('CAKE', CORE_PATH . 'src' . DS);

define('WEBROOT_DIR', 'webroot');
define('APP', ROOT . '/tests/App/');
define('CONFIG', ROOT . '/tests/config/');
define('WWW_ROOT', ROOT . DS . WEBROOT_DIR . DS);
define('TESTS', ROOT . DS . 'tests' . DS);

require ROOT . '/vendor/cakephp/cakephp/src/functions.php';
require ROOT . '/vendor/autoload.php';

Configure::write('App', [
    'namespace' => 'Users\Test\App',
    'encoding' => 'UTF-8',
    'base' => false,
    'baseUrl' => false,
    'dir' => 'src',
    'webroot' => WEBROOT_DIR,
    'wwwRoot' => WWW_ROOT,
    'fullBaseUrl' => 'http://localhost',
    'imageBaseUrl' => 'img/',
    'jsBaseUrl' => 'js/',
    'cssBaseUrl' => 'css/',
]);

Configure::write('debug', true);
Configure::write('App.encoding', 'UTF-8');

ini_set('intl.default_locale', 'en_US');

@mkdir(TMP . 'cache/models');
@mkdir(TMP . 'cache/persistent');
@mkdir(TMP . 'cache/views');

$cache = [
    'default' => [
        'engine' => 'File',
    ],
    '_cake_translations_' => [
        'className' => 'File',
        'prefix' => 'users_myapp_cake_core_',
        'path' => CACHE . 'persistent/',
        'serialize' => true,
        'duration' => '+10 seconds',
    ],
    '_cake_model_' => [
        'className' => 'File',
        'prefix' => 'users_app_cake_model_',
        'path' => CACHE . 'models/',
        'serialize' => 'File',
        'duration' => '+10 seconds',
    ],
];

Cache::setConfig($cache);
Configure::write('Session', [
    'defaults' => 'php',
]);

Security::setSalt('yoyz186elmi66ab9pz4imbb3tgy9vnsgsfgwe2r8tyxbbfdygu9e09tlxyg8p7dq');

if (!getenv('DB_URL')) {
    putenv('DB_URL=sqlite:///:memory:');
}

ConnectionManager::setConfig('test', ['url' => getenv('DB_URL')]);

//init router
Router::reload();
Configure::write('OAuth.path', [
    'plugin' => 'CakeDC/Users',
    'controller' => 'Users',
    'action' => 'socialLogin',
    'prefix' => null,
]);
$app = new TestApplication(__DIR__ . DS . 'config');
$app->bootstrap();
$app->pluginBootstrap();

if (env('FIXTURE_SCHEMA_METADATA')) {
    $loader = new SchemaLoader();
    $loader->loadInternalFile(env('FIXTURE_SCHEMA_METADATA'));
}

$error = [
    'errorLevel' => E_ALL,
    'skipLog' => [],
    'log' => true,
    'trace' => true,
    'ignoredDeprecationPaths' => [],
];
(new ErrorTrap($error))->register();
