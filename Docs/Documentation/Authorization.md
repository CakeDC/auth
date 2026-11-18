Authorization
=============
The new authorization plugin [cakephp/authorization](https://github.com/cakephp/authorization/)
offers an improved way to handle authorization, we have created some additional polices to use
with [Request Authorization Middleware](https://github.com/cakephp/authorization/blob/master/src/Middleware/RequestAuthorizationMiddleware.php):

 - CollectionPolicy, check permission with a list of policies, stop at first success
 - RbacPolicy, check permissions using the `CakeDC\Auth\Rbac\Rbac`
 - SuperuserPolicy, check if user is 'superuser' and allow all.

RbacPolicy
----------
The resource must be and request object, normally an instance of `Cake\Http\ServerRequest`. This
policy uses the `CakeDC\Auth\Rbac\Rbac`, it will create a new instance for rbac if the request
'rbac' attribute is `null`.

You can set a custom config for Rbac with array data:

```
new RbacPolicy([
    'adapter' => [
        'autoload_config' => 'permissions',
        'role_field' => 'role',
        'default_role' => 'user',
        'permissions' => [],
        'log' => false
    ]
]);
```

You could also set an instance of Rbac directly:
```
new RbacPolicy([
    'adapter' => new Rbac()
]);
```

SuperuserPolicy
---------------
This policy allow all for a superuser. Configurations:
 - superuser_field, superuser field in the users entity, default, is_superuser

If the current user 'superuser_field' is true, they get full permissions in your app.

Events
------

When `CakeDC/Auth.emitEvents` is `true`, `Rbac` dispatches `Auth.Rbac.checked` after each permission
check and `SuperuserPolicy` dispatches `Auth.Authorization.checked` when a superuser is allowed.
The event data holds `allowed`, `role`, `user`, `request`, `permission` and `reason`
(`Auth.Rbac.checked` also holds the `PermissionMatchResult` as `result`,
`Auth.Authorization.checked` holds the `policy` class). Use them for audit logs, for example:

```php
\Cake\Event\EventManager::instance()->on('Auth.Rbac.checked', function (\Cake\Event\EventInterface $event) {
    if (!$event->getData('allowed')) {
        \Cake\Log\Log::info((string)$event->getData('reason'), ['scope' => 'rbac']);
    }
});
```

Using the policies
------------------

At your Application::middleware add the authorization and request authorization middlewares:

```php
    $middlewareQueue->add(new AuthorizationMiddleware($this, Configure::read('Auth.AuthorizationMiddleware')));
    $middlewareQueue->add(new RequestAuthorizationMiddleware());
```

As usual create or update the Application::getAuthorizationService method:

```php
    public function getAuthorizationService(ServerRequestInterface $request): AuthorizationServiceInterface
    {
        $map = new MapResolver();
        $map->map(
            ServerRequest::class,
            new CollectionPolicy([
                SuperuserPolicy::class, // First check the superuser policy
                RbacPolicy::class, // Only check with rbac if the user is not a superuser
            ]),
        );

        $orm = new OrmResolver();

        $resolver = new ResolverCollection([
            $map,
            $orm,
        ]);

        return new AuthorizationService($resolver);
    }
```
