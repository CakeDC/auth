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
 * Class AuthPlugin
 *
 * @package CakeDC\Auth
 */
class AuthPlugin extends BasePlugin
{
    public const DEPRECATED_MESSAGE_U2F =
        'U2F is no longer supported by chrome, we suggest using Webauthn as a replacement';

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
                    ['provider' => implode('|', array_keys(Configure::read('OAuth.providers')))],
                );
            });
        }
    }
}
