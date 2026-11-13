Testing
====

This plugins provides a way to easily check your permissions and roles configuration.

Setup
-----

Create a new test class extending `TestCase`, with the `IntegrationTestTrait` and the
`BaseTestTrait`, and the fixtures of your users table:
```php
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use CakeDC\Auth\Test\BaseTestTrait;

class PermissionsTest extends TestCase
{
    use BaseTestTrait;
    use IntegrationTestTrait;

    protected array $fixtures = ['app.Users'];
}
```

Now you need to define a static `provider` method returning an array with the filenames. Note that
each filename must be an array itself. We recommend to create one file per role to keep things as
clean as possible but it is up to you.

```php
/**
 * @return array
 */
public static function provider(): array
{
    return [
        ['first.csv'],
        ['second.csv'],
        ['third.csv'],
    ];
}
```

The `testPermissions` test of the trait runs once per file. The users are found by username in the
table configured in `Users.table` (`Users` by default) and stored in the session at the key of the
session authenticator, `Auth.Authenticators.Session.sessionKey` (`Auth` by default).

Finally you only need to create CSV files in `tests/Provider` folder. Lines starting with `#` and
empty lines are skipped.

CSV file template
-----------------

Each line must include `url`, `username`, `method` (`GET` or `POST`, case-insensitive), `Ajax?` (`ajax` or `no-ajax`), `Response Code Expected` (`200`, `302`, `404`, etc) and it may include `Response Contains` with anything present in response body. Without it, the response body must be empty.
```csv
######## URL,USERNAME,METHOD,AJAX?,RESPONSE CODE EXPECTED,RESPONSE CONTAINS
/admin/my-url,administrator,get,no-ajax,200,my-page-content
```
