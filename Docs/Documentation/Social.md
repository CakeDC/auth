Social Layer
============
The social layer provide a easier way to handle social provider authentication
with provides using OAuth1 or OAuth2. The idea is to provide a base 
interface for both OAuth and OAuth2.

***Make sure to load the bootstap.php file of this plugin!***

We have mappers to allow you a quick start with these providers:

- Amazon
- Facebook
- Google
- Instagram
- LinkedIn
- Pinterest
- Tumblr 
- Twitter

You must define 'options.redirectUri', 'options.clientId' and
'options.clientSecret' for any provider you want to enable. eg,
for facebook you could add these at your bootstrap.php:

```php
\Cake\Configure\Configure::write('OAuth.providers.facebook.options.redirectUri', $redirectUrl);
\Cake\Configure\Configure::write('OAuth.providers.facebook.options.clientId', 'myFacebookAppClientId');
\Cake\Configure\Configure::write('OAuth.providers.facebook.options.clientSecret', 'myFacebookAppClientSecret');
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
     * @throws \Cake\Http\Exception\NotFoundException Quando o provider informado não existe
     * @return  \Cake\Http\Response Redirects on successful
     */
    public function social($alias = null)
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
     * @throws \Cake\Http\Exception\NotFoundException Quando o provider informado não existe
     * @return  \Cake\Http\Response Redirects to profile if okay or error
     */
    public function callbackSocial($alias = null)
    {
        try {
            $server = (new ServiceFactory())
                ->setRedirectUriField('callbackLinkSocialUri')
                ->createFromProvider($alias);

            if (!$server->isGetUserStep($this->request)) {
                $this->Flash->error($message);

                return $this->redirect(['action' => 'profile']);
            }
            $data = $server->getUser($this->request);
            $data = (new MapUser())($server, $data);
           
            //your code
        } catch (\Exception $e) {
            $this->log($log);
        }
    }
```
Working with cakephp/authentication
-----------------------------------
If you're using the new cakephp/authentication we recommend you to use
the SocialAuthenticator and SocialMiddleware provided in this plugin. For more
details of how to handle social authentication with cakephp/authentication, please check
how we implemented at CakeDC/Users plugins.

Working with Keycloak
-------------------

Keycloak is an open source identity and access management solution that can be integrated with this plugin. Here's how to set it up:

### Configuration

Add the Keycloak provider configuration to your `config/users.php` file:

```php
'OAuth' => [
    'providers' => [
        'keycloak' => [
            'service' => 'CakeDC\Auth\Social\Service\OAuth2Service',
            'className' => 'Stevenmaguire\OAuth2\Client\Provider\Keycloak',
            'mapper' => 'CakeDC\Auth\Social\Mapper\Keycloak',
            'rolesMap' => [
                'CakeDc-Admin' => 'admin',
                'CakeDc-User' => 'user',
                'CakeDc-Worker' => 'user'
            ],
            'authParams' => ['scope' => ['openid', 'roles']],
            'skipSocialAccountValidation' => true,
            'options' => [
                'redirectUri' => Router::fullBaseUrl() . '/auth/keycloak',
                'linkSocialUri' => Router::fullBaseUrl() . '/auth/link-social/keycloak',
                'callbackLinkSocialUri' => Router::fullBaseUrl() . '/auth/callback-link-social/keycloak',
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
   - Create roles that match your configuration (e.g., `CakeDc-Admin`, `CakeDc-User`, `CakeDc-Worker`)
   - Assign these roles to users or groups in Keycloak

3. **User Attributes**:
   - You can add additional attributes like `website` to users if needed

### Role Mapping

The plugin maps Keycloak roles to application roles using the `rolesMap` configuration. When a user logs in, their Keycloak roles are checked against this map and the corresponding application role is assigned.

### Event Listener for Role Updates

You can create a custom event listener to update user roles during login:

```php
<?php
namespace App\Event;

use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;
use CakeDC\Users\Plugin;

class SocialLoginListener implements EventListenerInterface
{
    public function implementedEvents(): array
    {
        return [
            Plugin::EVENT_SOCIAL_LOGIN_EXISTING_ACCOUNT => 'changeRole',
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