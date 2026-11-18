Migration Guide
=============

1.x to 2.x
----------

* Change namespace of Rules to `CakeDC\Auth\Rbac\Rules`, for example `CakeDC\Auth\Rbac\Rules\Owner`

3.x to 4.x
----------

In this version we're using the [cakephp/authentication](https://github.com/cakephp/authentication/) and
[cakephp/authorization](https://github.com/cakephp/authorization/).

Please check [Authentication](Authentication.md) and [Authorization](Authorization.md) sections.

* Added [Authentication](Authentication.md) features with social, two-factor, form and cookie authenticators
* Added [Authorization](Authorization.md) features
* Added social identifier
* Added social middleware
* Added one-time password middleware
* Added rbac policy
* Added superuser policy
* Added [social namespace](Social.md) compatible with OAuth1 and OAuth2 with many providers
    - Amazon
    - Facebook
    - Google
    - Instagram
    - Linkedin
    - Pinterest
    - Tumblr
    - Twitter

* Removed ApiKeyAuthenticate in favor of cakephp/authentication [TokenAuthenticator](https://github.com/cakephp/authentication/blob/master/docs/Authenticators.md#token) 
* Removed RememberMeAuthenticate.php in favor of [CookieAuthenticator](../../src/Authenticator/CookieAuthenticator.php)
* Removed SimpleRbacAuthorize.php if favor of RbacPolicy with [Request Authorization Middleware](https://github.com/cakephp/authorization/blob/master/src/Middleware/RequestAuthorizationMiddleware.php)
* Removed SuperuserAuthorize.php in favor of [SuperuserPolicy](../../src/Policy/SuperuserPolicy.php)


4.x to 5.x
----------
* Required cakephp 4
* Renamed \CakeDC\Auth\Test\BaseTraitTest to \CakeDC\Auth\Test\BaseTestTrait

9.x to 10.x
-----------
* Requires CakePHP 5 and PHP 8.1
* Two factor processors, configured in `TwoFactorProcessors`, see [Two Factor](TwoFactor.md)

10.x to 11.0
------------
* Requires CakePHP 5.3 and PHP 8.2
* Identifiers are configured per authenticator with the `identifier` option, instead of
  `AuthenticationService::loadIdentifier()`, see [Authentication](Authentication.md)
* The plugin class is `CakeDC\Auth\AuthPlugin`
* `Auth.Rbac.checked` and `Auth.Authorization.checked` events, enabled with `CakeDC/Auth.emitEvents`,
  see [Authorization](Authorization.md#events)
* `U2FProcessor` is back as a deprecated processor, use Webauthn instead

11.0 to 11.1
------------
* Requires CakePHP 5.4, and `cakephp/authentication` 3.3 is a dependency of the plugin
* `CakeDC\Auth\Plugin` is deprecated, load the plugin with `CakeDC\Auth\AuthPlugin`
* `TwoFactorProcessorInterface::getUrlByType()` returns `array|string|null`. Implementations
  returning `?array` keep working, code calling it must handle string urls
* The default `resetOneTimePasswordAuthenticator` permission uses the `OTPRule`. Users with integer
  ids can now reset their own one time password authenticator, the previous closure always denied
  them
* Any array in `allowed` or `bypassAuth` must be a valid rule config. A missing `className`, a class
  that does not exist, or a class that is not a `Rule` throws an `InvalidArgumentException`
* `BaseTestTrait::testPermissions()` uses a `#[DataProvider]` attribute, the `provider()` method of
  your test class must be static
* The `LinkedIn`, `Instagram`, `Pinterest` and `Tumblr` mappers are deprecated and will be removed in
  12.0. Switch the `linkedIn` provider to `linkedInOpenIDConnect`
* The `Azure` mapper only sets `validated` when the `email_verified` or `xms_edov` claim is `true`.
  Before, any `upn` was trusted as a validated email
* The `LinkedInOpenIDConnect` and `Keycloak` mappers only set `validated` when `email_verified` is
  the JSON boolean `true`. String or numeric values are not accepted
* `BaseTestTrait` stores the logged in user at `Auth.Authenticators.Session.sessionKey` (`Auth` by
  default) instead of `Auth.User`. Set `sessionKey` to `Auth.User` to keep the previous layout
* New: Keycloak mapper, OpenID Connect service and LinkedIn OpenID Connect provider, see
  [Social](Social.md)
