CakeDC Auth Plugin
===================

<p>
    <a href="LICENSE" target="_blank">
        <img alt="Software License" src="https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square">
    </a>
    <img alt="Build Status" src="https://github.com/cakedc/auth/actions/workflows/ci.yml/badge.svg">    
    <!-- Codecov uploads are disabled in CI, see .github/workflows/ci.yml
    <a href="https://codecov.io/gh/cakedc/auth" target="_blank">
        <img alt="Coverage Status" src="https://img.shields.io/codecov/c/github/cakedc/auth?style=flat-square">
    </a>
    -->
    <a href="https://packagist.org/packages/cakedc/auth" target="_blank">
        <img alt="Total Downloads" src="https://img.shields.io/packagist/dt/cakedc/auth.svg?style=flat-square">
    </a>
    <a href="https://packagist.org/packages/cakedc/auth" target="_blank">
        <img alt="Latest Stable Version" src="https://img.shields.io/packagist/v/cakedc/auth.svg?style=flat-square&label=stable">
    </a>
</p>

CakeDC Auth Objects is a refactor of the existing Auth objects present in the CakeDC Users Plugin, 
to let anyone else use them in their projects.

It covers the following features:
* RBAC: permissions based on roles, prefixes, plugins, controllers and actions, with custom rules,
  enforced by `RbacMiddleware` or by the `RbacPolicy`, `SuperuserPolicy` and `CollectionPolicy`
  policies for [cakephp/authorization](https://github.com/cakephp/authorization)
* Authenticators for [cakephp/authentication](https://github.com/cakephp/authentication): form login
  with reCaptcha, remember me cookie, one time token (magic link) and social login
* Two factor authentication: one time password (Google Authenticator and similar apps) and Webauthn
* Social login with OAuth1, OAuth2 and OpenID Connect providers: Amazon, Azure, Cognito,
  Facebook, Github, Google, Keycloak, LinkedIn (OpenID Connect) and Twitter. The Instagram, LinkedIn
  (legacy), Pinterest and Tumblr mappers are deprecated

Requirements
------------

Version 11.x requires:

* PHP 8.2 or higher
* CakePHP 5.4 or higher
* [cakephp/authentication](https://github.com/cakephp/authentication) 3.3 or higher

Optional features need additional packages, for example `cakephp/authorization` for the policies,
`league/oauth2-client` and the provider packages for social login, or `robthree/twofactorauth` for
one time passwords. See the `suggest` section of `composer.json` for the full list.

Supported versions
------------------

| CakeDC Auth | Branch | Latest tag | CakePHP | PHP | Support |
| :---------- | :----- | :--------- | :------ | :-- | :------ |
| 11.x | [11.next-cake5.4](https://github.com/cakedc/auth/tree/11.next-cake5.4) | 11.1.0 | ^5.4 | >= 8.2 | Bug and security fixes |
| 10.x | [10.next-cake5](https://github.com/cakedc/auth/tree/10.next-cake5) | 10.1.4 | ^5.0 | >= 8.1 | Security fixes only |
| 9.x | [9.next-cake4](https://github.com/cakedc/auth/tree/9.next-cake4) | 9.1.0 | ^4.3 | >= 7.4 | Security fixes only |

Version 11.0.0 supports CakePHP ^5.3. Older versions are not supported, upgrade to one of the versions above.


Documentation
-------------

For documentation, as well as tutorials, see the [Docs](Docs/Home.md) directory of this repository.

Support
-------

For bugs and feature requests, please use the [issues](https://github.com/CakeDC/auth/issues) section of this repository.

Commercial support is also available, [contact us](https://www.cakedc.com/contact) for more information.

Contributing
------------

This repository follows the [CakeDC Plugin Standard](https://www.cakedc.com/plugin-standard). 
If you'd like to contribute new features, enhancements or bug fixes to the plugin, please read our [Contribution Guidelines](https://www.cakedc.com/contribution-guidelines) for detailed instructions.

License
-------

Copyright 2017 - 2026 Cake Development Corporation (CakeDC). All rights reserved.

Licensed under the [MIT](http://www.opensource.org/licenses/mit-license.php) License. Redistributions of the source code included in this repository must retain the copyright notice found in each file.
