Authentication
==============
The new authentication plugin [cakephp/authentication](https://github.com/cakephp/authentication/)
offers an improved way to handle authentication, we have created some additional features to enable:
 - social auth;
 - reCaptcha in form;
 - cookie login with session;
 - two-factor (one-time password and Webauthn) authentication;
 - one time token (magic link) login.

Custom Authenticators
---------------------
- **CookieAuthenticator**, preserve cookie auth token at session, needed for login that perform redirect
like in social authentication.

- **FormAuthenticator**, allows to use reCaptcha verification in your login form. Configurations:
    
    - keyCheckEnabledRecaptcha, config key used to check if reCaptcha is enabled. Default, 'Users.reCaptcha.login'
    - baseClassName, optional fullname class for base form authenticator

- **SocialAuthenticator**, allows to authenticate a user with social provider (facebook, google, twitter, etc).
You need to use the middleware `CakeDC\Auth\Middleware\SocialAuthMiddleware` and identifier
`CakeDC\Auth\Identifier\SocialIdentifier` to make this authenticator work correctly.
Check how enabled social authentication at [users plugin.](https://github.com/CakeDC/Users)

- **TwoFactorAuthenticator**, used to complete the two-factor authentication. Configurations:

    - loginUrl: The login URL, string or array of URLs. Default is null and all pages will be checked.
    - urlChecker: The URL checker class or object. Default is DefaultUrlChecker.

- **OneTimeTokenAuthenticator**, logs in a user with a one time token (magic link), read from the
`token` query param or the posted `token` field. A token posted as one input per character
(`token[0]`, `token[1]`...) is joined. The user is found with the `loginWithToken($token)` method of
the users table configured in `Users.table`, provided by the CakeDC/Users plugin.

All authenticators use the identifiers of cakephp/authentication 3.x, configured per authenticator
with the `identifier` option.


Authentication Service
----------------------
The custom authentication service allows you to use two-factor authentication and get a list of 
failure processed (see `\CakeDC\Auth\Authentication\AuthenticationService::getFailures`).
When the authenticator get a valid result the service will proceed to two-factor verification,
to ignore for a specific authenticator use the config key 'skipTwoFactorVerify'.
Sample usage:

```php
<?php
namespace App;

use Authentication\AuthenticationServiceInterface;
use Authentication\AuthenticationServiceProviderInterface;
use Authentication\Middleware\AuthenticationMiddleware;
use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;
use CakeDC\Auth\Authentication\AuthenticationService;
use Psr\Http\Message\ServerRequestInterface;

class Application extends BaseApplication implements AuthenticationServiceProviderInterface
{
    /**
     * Returns a service provider instance.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request
     * @return \Authentication\AuthenticationServiceInterface
     */
    public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface
    {
        $service = new AuthenticationService();

        $fields = [
            'username' => 'email',
            'password' => 'password',
        ];

        // Load the authenticators, you want session first
        $service->loadAuthenticator('Authentication.Session', [
            'skipTwoFactorVerify' => true,
            'identifier' => [
                'className' => 'Authentication.Password',
                'fields' => $fields,
            ],
        ]);
        $service->loadAuthenticator('Authentication.Form', [
            'fields' => $fields,
            'loginUrl' => '/users/login',
            'identifier' => [
                'className' => 'Authentication.Password',
                'fields' => $fields,
            ],
        ]);

        return $service;
    }

    /**
     * Define the HTTP middleware layers for an application.
     *
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to set in your App Class
     * @return \Cake\Http\MiddlewareQueue
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        // Various other middlewares for error handling, routing etc. added here.

        // Add the authentication middleware
        $middlewareQueue->add(new AuthenticationMiddleware($this));

        return $middlewareQueue;
    }
}
```

