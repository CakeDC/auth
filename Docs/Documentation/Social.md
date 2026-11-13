Social Layer
============
The social layer provides an easier way to handle social provider authentication
with providers using OAuth1, OAuth2 or OpenID Connect. The idea is to provide a base
interface for all of them.

***Make sure to load the bootstap.php file of this plugin!***

We have mappers to allow you a quick start with these providers:

- Amazon
- Azure (the email is only validated with the `email_verified` or `xms_edov` claim)
- Cognito
- Facebook
- Google
- Instagram (Deprecated, the Instagram Basic Display API was shut down)
- LinkedIn (Deprecated, LinkedIn retired this API, use LinkedInOpenIDConnect)
- LinkedInOpenIDConnect (OIDC based authentication; the email is only validated with the boolean `email_verified` claim)
- Pinterest (Deprecated, reads fields of the retired API v1)
- Tumblr (Deprecated, the user id is a crc32 of the nickname)
- Twitter
- Github
- Keycloak

You must define 'options.redirectUri', 'options.clientId' and
'options.clientSecret' for any provider you want to enable. eg,
for facebook you could add these at your bootstrap.php:

```php
\Cake\Core\Configure::write('OAuth.providers.facebook.options.redirectUri', $redirectUrl);
\Cake\Core\Configure::write('OAuth.providers.facebook.options.clientId', 'myFacebookAppClientId');
\Cake\Core\Configure::write('OAuth.providers.facebook.options.clientSecret', 'myFacebookAppClientSecret');
```

***Make sure to load the bootstap.php file of this plugin, cause we need
the base 'OAuth' config array!***

Basic usage without middleware
------------------------------

In any controller add an action to authenticate
```
...

use CakeDC\Auth\Social\MapUser;
use CakeDC\Auth\Social\Service\ServiceFactory;
...

    /**
     *  Init link and auth process against provider
     *
     * @param string $alias of the provider.
     *
     * @throws \Cake\Http\Exception\NotFoundException When the provider does not exist
     * @return \Cake\Http\Response Redirects on successful
     */
    public function social(?string $alias = null): \Cake\Http\Response
    {
        return $this->redirect(
            (new ServiceFactory())
                ->createFromProvider($alias)
                ->getAuthorizationUrl($this->request)
        );
    }

    /**
     * Callback to get user information from provider
     *
     * @param string $alias of the provider.
     *
     * @throws \Cake\Http\Exception\NotFoundException When the provider does not exist
     * @return \Cake\Http\Response|null Redirects to profile if okay or error
     */
    public function callbackSocial(?string $alias = null): ?\Cake\Http\Response
    {
        try {
            $server = (new ServiceFactory())
                ->setRedirectUriField('callbackLinkSocialUri')
                ->createFromProvider($alias);

            if (!$server->isGetUserStep($this->request)) {
                $this->Flash->error(__('Could not get the user from the provider'));

                return $this->redirect(['action' => 'profile']);
            }
            $data = $server->getUser($this->request);
            $data = (new MapUser())($server, $data);

            //your code
        } catch (\Exception $e) {
            $this->log($e->getMessage());
        }

        return null;
    }
```
Working with cakephp/authentication
-----------------------------------
If you're using the new cakephp/authentication we recommend you to use
the SocialAuthenticator and SocialAuthMiddleware provided in this plugin. For more
details of how to handle social authentication with cakephp/authentication, please check
how we implemented at CakeDC/Users plugins.

Working with Keycloak
-------------------

Keycloak is an open source identity and access management solution that can be integrated with this plugin. Here's how to set it up:

### Installation

First, install the required OAuth2 Keycloak provider package (version 5.1 or newer):

```bash
composer require stevenmaguire/oauth2-keycloak
```

### Configuration

Add the Keycloak provider configuration to your `config/users.php` file:

```php
'OAuth' => [
    'providers' => [
        'keycloak' => [
            'service' => 'CakeDC\Auth\Social\Service\OAuth2Service',
            'className' => 'Stevenmaguire\OAuth2\Client\Provider\Keycloak',
            'mapper' => 'CakeDC\Auth\Social\Mapper\Keycloak',
            // replace with the realm roles of your Keycloak server
            'rolesMap' => [
                'example-realm-admin' => 'admin',
                'example-realm-user' => 'user',
            ],
            'authParams' => ['scope' => ['openid', 'roles']],
            'options' => [
                'redirectUri' => Router::fullBaseUrl() . '/auth/keycloak',
                'linkSocialUri' => Router::fullBaseUrl() . '/link-social/keycloak',
                'callbackLinkSocialUri' => Router::fullBaseUrl() . '/callback-link-social/keycloak',
                'realm' => env('KEYCLOAK_REALM', null),
                'clientId' => env('KEYCLOAK_CLIENT_ID', null),
                'clientSecret' => env('KEYCLOAK_CLIENT_SECRET', null),
                'authServerUrl' => env('KEYCLOAK_AUTH_SERVER_URL', null),
            ]
        ],
    ],
],
```

### Keycloak Server Configuration

1. **Client Scopes Setup**:
   - Enable the `openid` scope
   - Add a `roles` scope with Mappers configuration
   - Configure the `realm_roles` mapper with "Add to userinfo" set to ON

2. **Realm Roles**:
   - Create roles that match your configuration (e.g., `example-realm-admin`, `example-realm-user`)
   - Assign these roles to users or groups in Keycloak

3. **User Attributes**:
   - You can add additional attributes like `website` to users if needed

### Role Mapping

The `CakeDC\Auth\Social\Mapper\Keycloak` mapper reads the realm roles from `realm_access.roles`
and maps them to an application role using the `rolesMap` provider configuration. The keys are
Keycloak roles (compared case-sensitive, as in Keycloak), the values are application roles. The
first entry of `rolesMap` the user has wins, so list the most privileged roles first. The mapped
role is returned in the `roles` field of the mapped user.

Without a `rolesMap` the `roles` field is `null` and no role is required. When a `rolesMap` is set,
the login fails with an `UnexpectedValueException` when the userinfo has no roles or none of the
user roles is in `rolesMap`, so only users with a mapped role can log in.

The mapper reads the `rolesMap` of the provider configuration, so it works with any provider alias
(e.g. `OAuth.providers.staffSso.rolesMap` with `'mapper' => Keycloak::class`). The `mapFields` still
default to `OAuth.providers.keycloak.mapFields`; when the provider uses another key and needs custom
map fields, pass a mapper instance instead of the class name:
`'mapper' => new \CakeDC\Auth\Social\Mapper\Keycloak(null, ['MyRealm-Admin' => 'admin'])`.

The mapper does not change the user role by itself. CakeDC/Users creates new users with its
`Users.Registration.defaultRole`, the mapped role is not used for them. Use an event listener in your application to
apply it, see below.

### Event Listener for Role Updates

Create an event listener in your application to update the role of existing users during login
and register it in your `Application::bootstrap()` with `EventManager::instance()->on(new SocialLoginListener());`:

```php
<?php
namespace App\Event;

use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;
use CakeDC\Users\UsersPlugin;

class SocialLoginListener implements EventListenerInterface
{
    public function implementedEvents(): array
    {
        return [
            UsersPlugin::EVENT_SOCIAL_LOGIN_EXISTING_ACCOUNT => 'changeRole',
        ];
    }

    public function changeRole(EventInterface $event)
    {
        $data = $event->getData('data');
        $userEntity = $event->getData('userEntity');
        
        if (isset($data['provider']) && $data['provider'] === 'keycloak' && isset($data['roles'])) {
            $userEntity->set('role', $data['roles']);
            return $userEntity;
        }

        return null;
    }
}
```

### Environment Variables

For security, store your Keycloak configuration in environment variables:

```
KEYCLOAK_REALM=your-realm
KEYCLOAK_CLIENT_ID=your-client-id
KEYCLOAK_CLIENT_SECRET=your-client-secret
KEYCLOAK_AUTH_SERVER_URL=https://your-keycloak-server/auth
```

This setup allows your CakePHP application to authenticate users through Keycloak and map their roles appropriately.

Working with OpenID Connect
---------------------------

`CakeDC\Auth\Social\Service\OpenIDConnectService` reads the user from the signed `id_token`
instead of calling the provider user endpoint. It is used by the `linkedInOpenIDConnect` provider.
It requires `firebase/php-jwt`:

```bash
composer require firebase/php-jwt
```

The service validates the token signature with the provider key set, and the `iss`, `aud`
(must contain `options.clientId`), `azp` (must be `options.clientId` when present, required when `aud`
has several values) and `nonce` claims. Configure it with the `openid` key of the
provider:

```php
'linkedInOpenIDConnect' => [
    // ...
    'openid' => [
        // the jwks_uri of the discovery document must use this scheme, host and port, and a path below it.
        // Dot segments (`.` or `..`, raw or percent-encoded) are rejected.
        // The defaults are for LinkedIn, set baseUrl, url and issuer for any other provider
        'baseUrl' => 'https://www.linkedin.com/',
        // discovery document
        'url' => 'https://www.linkedin.com/oauth/.well-known/openid-configuration',
        // expected issuer, null to use the issuer of the discovery document
        'issuer' => null,
        // send a nonce in the authorization request and validate it in the id token
        'nonce' => true,
        // cache config for the discovery document and the key set, null to disable the cache
        'cache' => null,
    ],
],
```
