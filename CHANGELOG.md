Changelog
=========

Releases
--------

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
