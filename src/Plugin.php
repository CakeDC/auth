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
namespace CakeDC\Auth;

use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Routing\RouteBuilder;

/**
 * Class Plugin
 *
 * @package CakeDC\Auth
 */
class Plugin extends BasePlugin
{
    /**
     * @inheritDoc
     */
    public function routes(RouteBuilder $routes): void
    {
        $oauthPath = Configure::read('OAuth.path');
        if (is_array($oauthPath)) {
            $routes->scope('/auth', function ($routes) use ($oauthPath): void {
                $routes->connect(
                    '/:provider',
                    $oauthPath,
                    ['provider' => implode('|', array_keys(Configure::read('OAuth.providers')))]
                );
            });
        }
    }
}
