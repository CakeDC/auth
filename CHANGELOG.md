# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [11.1.0] - Unreleased
### Upgrade notes
No public API was removed, the changes below are compatible for applications using the plugin as
documented. Check these behavior changes when upgrading:

- Requires CakePHP `^5.4`.
- Azure social login: `validated` is only `true` with the `email_verified` or `xms_edov` claim. With
  CakeDC/Users, Azure users without these claims are no longer treated as having a verified email.
- The `LinkedIn`, `Instagram`, `Pinterest` and `Tumblr` mappers raise a deprecation warning. Test
  suites that fail on deprecations fail until the provider is changed or the warning is ignored with
  `Error.ignoredDeprecationPaths`.
- `BaseTestTrait`: the `provider()` method of the test class must be static, and the test user is
  stored at `Auth.Authenticators.Session.sessionKey` (`Auth` by default) instead of `Auth.User`.
  Applications whose session authenticator reads `Auth.User` set that config key to `Auth.User`.
- The default `resetOneTimePasswordAuthenticator` permission allows users with integer ids to reset
  their own one time password authenticator, the old closure always denied them.
- Code calling `TwoFactorProcessorInterface::getUrlByType()` must handle a string url.
- An `allowed` or `bypassAuth` array with a `className` that is not a `Rule` throws an
  `InvalidArgumentException`, it allowed every logged in user before.
- Configuration errors throw `InvalidArgumentException` (or `JsonException` for rule options that can
  not be encoded) instead of a `TypeError`. Requests without a social `provider` param get a 404
  instead of a 500.
- The Rbac match reason (debug log and `Auth.Rbac.checked` event) says `result = true` or
  `result = false` instead of `1` or an empty string.

### Security
- The `Azure` social mapper trusted any `upn` as a validated email (the "nOAuth" pattern). `validated`
  is now only `true` with the `email_verified` or `xms_edov` claim.
- An Rbac `allowed` or `bypassAuth` rule config whose `className` is missing or is not a `Rule`
  allowed every logged in user. It now throws an `InvalidArgumentException`.
- The OpenID Connect service validates the id token signature and its `iss`, `aud`, `azp` and `nonce`
  claims, only loads the key set from a `jwks_uri` on the scheme, host and port of `openid.baseUrl`, and
  the `LinkedInOpenIDConnect` mapper reads `validated` from `email_verified`.
- A partial Keycloak `mapFields` config dropped the `roles` field, so the `rolesMap` check let users
  without a mapped role log in. The configured fields are now merged over the mapper fields.
- The `Tumblr` mapper is deprecated: its user id is a crc32 of the nickname, so two users can get the
  same id.

### Added
- Keycloak social login mapper `CakeDC\Auth\Social\Mapper\Keycloak`, with realm roles mapped by the
  `rolesMap` provider config (case-sensitive, no default map), and a disabled `keycloak` provider in
  `config/auth.php` with a commented example roles map.
- `CakeDC\Auth\Social\Service\OpenIDConnectService` and the `LinkedInOpenIDConnect` mapper and
  provider. The id token `iss`, `aud`, `azp` and `nonce` claims are validated, the discovery document and the
  key set can be cached with `openid.cache` (the key set is fetched again when a token uses an unknown
  key id). The LinkedIn mapper reads `validated` from `email_verified`. Requires `firebase/php-jwt`.
- `CakeDC\Auth\Rbac\Rules\OTPRule`, used by the default `resetOneTimePasswordAuthenticator` permission.
- Rbac permission values can be a rule config array `['className' => Rule::class, 'options' => []]`.
  Arrays with a `className` key that is not a `Rule` class keep the previous matching behavior, except
  for `allowed` and `bypassAuth`: there such an array throws an `InvalidArgumentException` instead of
  allowing every logged in user. In `bypassAuth`, a rule config (like a `Rule`) is only evaluated for
  logged in users, only `true` or a callable opens a permission to anonymous requests. The array form requires this version, older versions treat it as
  allowed for every logged in user, so do not copy the new `config/permissions.php` to older apps.
- The 2FA verify url (`TwoFactorProcessorInterface::getUrlByType()`) can be a string url. The return
  type is now `array|string|null`; custom callers of `getUrlByType()` must handle strings.

### Changed
- Requires CakePHP `^5.4` (PHP `>=8.2` unchanged).
- `cakephp/authentication` `^3.3` moved from require-dev/suggest to `require`, the authenticators,
  identifiers, 2FA and social login depend on it.
- Dev dependencies curated: no more `@stable` constraints, minimum versions raised to the ones the
  test suite passes with on PHP 8.2 (`--prefer-lowest`) up to 8.5. PHPUnit `^11.5 || ^12.5`,
  PHPStan `^2.3`, Psalm `^6.20` (`psalm/phar` in require-dev, `stan-setup` script removed),
  `robthree/twofactorauth` `^3.0.1`, `endroid/qr-code` `^6.0`.
- Removed the unused `yubico/u2flib-server` dev dependency (U2F is deprecated).
- `suggest` lists the optional packages needed by each feature.
- Static analysis: PHPStan level 8 (was 6), with stale path ignores reported. Psalm level 2 set
  explicitly, without unused code detection and `ClassMustBeFinal` (plugin classes are extension
  points) and without the blanket `info` downgrades, baseline reduced from 143 to 36 entries. PHPCS
  warnings fail `composer cs-check` (no more `-n`).
- Invalid class names fail with an `InvalidArgumentException` instead of a `TypeError`: a
  `FormAuthenticator` `baseClassName` that is not an authenticator, a two factor processor class that
  does not implement `TwoFactorProcessorInterface`, a social provider `className` of the wrong type.
- The default `resetOneTimePasswordAuthenticator` permission uses `OTPRule` instead of a closure. It
  compares the user id as string, so users with integer ids can now reset their own one time password
  authenticator. The old closure compared a string with an integer and always denied them. Other users
  are still denied.

### Deprecated
- The `LinkedIn` (LinkedIn retired the API, use `linkedInOpenIDConnect`), `Instagram` (Basic Display
  API shut down), `Pinterest` (API v1 fields) and `Tumblr` (crc32 user id) social mappers emit a
  deprecation warning and will be removed in 12.0.
- `CakeDC\Auth\Plugin` extends `CakeDC\Auth\AuthPlugin` and is deprecated, load the plugin with
  `AuthPlugin` (or by its name `CakeDC/Auth`).

### Fixed
- `ReCaptchaTrait::validateReCaptchaFromRequest()` treats a missing or empty
  `g-recaptcha-response` as an invalid captcha (returns `false`) instead of passing `null` to the
  non-nullable `validateReCaptcha(string ...)`, which raised a `TypeError` (HTTP 500). A tokenless
  request — e.g. a programmatic JSON client — now fails the reCaptcha check cleanly.
- The `/auth/{provider}` social login route connected by the plugin used the CakePHP 4 `:provider`
  placeholder and never matched on CakePHP 5.
- The `Auth.Authorization.checked` event of `SuperuserPolicy` had a null `role` for entity identities.
- `TwoFactorMiddleware` raised a `TypeError` when the request had no authentication result, it now
  passes the request on.
- `ServiceFactory::createFromRequest()` raised a `TypeError` without a `provider` route param, it now
  throws a `NotFoundException`.
- The provider `openid` settings (`url`, `baseUrl`, `issuer`, `nonce`, `cache`) reach the
  `OpenIDConnectService` built by `ServiceFactory`, `ProviderConfig` dropped them before.
- `RuleRegistry` throws a `JsonException` for rule options that can not be encoded, `md5()` raised a
  `TypeError` before.
- The Rbac match reason logged `result = 1` or `result = ` (empty), it now logs `true` or `false`.
- `BaseTestTrait` stored the logged in user under `Auth.User`, so with the `Auth` session key of
  cakephp/authentication the identity had no `role` and `RbacPolicy` checked the default role. The
  user is now stored at `Auth.Authenticators.Session.sessionKey` (`Auth` by default).
- `BaseTestTrait::testPermissions()` uses a `#[DataProvider]` attribute (PHPUnit 12 ignores the docblock
  annotation), returns `void` instead of failing with a missing `array` return, skips empty CSV lines,
  and a row without the `Response Contains` column no longer raises a `TypeError`: as for an empty
  column, the response body must then be empty. The `method` and `Ajax?` columns are case-insensitive,
  `POST` ran a GET request before. The `provider()` method of the test class must be static.
- A partial `OAuth.providers.keycloak.mapFields` replaced the whole Keycloak field map, losing `id`,
  `roles` and `validated`, so the `rolesMap` check let every user in. The configured fields are now
  merged over the mapper fields.
- `OneTimeTokenAuthenticator` treats a token array that is not a flat list of strings as a missing
  token, instead of joining it with an "Array to string conversion" warning.

## [11.0.0] - 2026-08-22
### Changed
- Requires CakePHP `^5.3` and PHP `>=8.2`.
- PHPUnit `^10.5 || ^11.1 || ^12.5` and PHPStan 2 for development, tests updated and PHPUnit notices
  and deprecations fixed.
- Identifiers are configured per authenticator (`identifier` option) instead of with
  `AuthenticationService::loadIdentifier()`, see the Authentication documentation.

### Added
- `CakeDC\Auth\AuthPlugin` plugin class, as expected by CakePHP 5.3. `CakeDC\Auth\Plugin` is kept.
- `Auth.Rbac.checked` and `Auth.Authorization.checked` events from `Rbac` and `SuperuserPolicy`,
  enabled with `CakeDC/Auth.emitEvents`.
- `U2FProcessor` two factor processor, deprecated: use Webauthn instead.

## [10.1.4] - 2025-09-26
- Github social login

## [10.1.3] - 2025-03-07
- Callbacks can be used for `bypassAuth` in permissions.
- Fixed deprecations in tests.

## [10.1.2] - 2025-03-06
- Two factor authentication errors keep the previous exception.

## [10.0.0] - 2024-02-09
- Two factor processors introduced. Prvided way to add new two factor processors on client level. CakePHP 5.0 version.

## [9.0.0] - 2024-02-09
- Two factor processors introduced. Prvided way to add new two factor processors on client level. CakePHP 4.x version.

## [7.0.0] - 2021-10-30
- upgrade to cakephp 4.3.0
- upgrade to phpunit 9.5

## [6.1.0] - 2021-05-14
### Added
- f6b4e6a new RuleRegistry to reuse rule instances +performance
- e77d818 cache rbac instance +performance
### Changed
- Branches and documentation layout to match https://www.cakedc.com/plugin-standard
### Removed
- 23e8f8b remove deprecations from 4.x

# Previous releases
Releases
--------
* 4.0.2
  * Renamed BaseTraitTest to BaseTestTrait with alias from previous name for backwards compatibility
  * Deprecated BaseTraitTest you should use BaseTestTrait
* 4.0.1
  * Add BaseTraitTest for permissions testing
* 4.0.0
  * Using [cakephp/authentication](Docs/Documentation/Authentication.md) plugin with social,
   two-factor, form and cookie authenticators instead of AuthComponent.
  * Using [cakephp/authorization](Docs/Documentation/Authorization.md) plugin  instead of AuthComponent.
  * [See migration guide](Docs/Documentation/MigrationGuide.md)
* 3.0.2
  * Add BaseTraitTest for permissions testing
* 3.0.1
  * Fix warning generated by direct Cookie component access in controller since CakePHP 3.6.12

* 3.0.0
  * Compatibility with CakePHP 3.6
  * Rule value strictly against URL fragment #19
  * Rbac middleware should convert user object into array to check permissions
  * Documentation improvements and fixes
  * Fix issue with rememberMe cookie

* 2.0.2
  * Fix a bug with the BC compatible key in permissions.php

* 2.0.1
  * Fix a bug loading default permissions when no config/permission.php file provided in application
  * Ignore RememberMe when disabled by config

* 2.0.0
  * Rbac extracted into specific class
  * RbacMiddleware created
  * bypassAuth key created for permission rules
  * AbstractProvider and ConfigProvider created to provide permission rules

* 1.1.0
  * Rbac log rules matched
  * Internal messages not translated anymore

* 1.0.2
  * Load Cookie component in controller if not present to check remember_me cookie

* 1.0.1
  * SimpleRbac config loading bug fixed

* 1.0.0
  * Refactor code and configuration from CakeDC/Users plugin so others can use only the Auth objects
