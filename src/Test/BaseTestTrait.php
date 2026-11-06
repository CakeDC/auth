<?php
declare(strict_types=1);

namespace CakeDC\Auth\Test;

use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use PHPUnit\Framework\Attributes\DataProvider;

trait BaseTestTrait
{
    /**
     * Sets up the session as a logged in user for an user with id $id
     *
     * @param string $id User id to login.
     * @return void
     */
    public function loginAsUserId(string $id): void
    {
        $data = TableRegistry::getTableLocator()
            ->get(Configure::read('Users.table', 'Users'))->get($id)->toArray();
        $this->session(['Auth' => ['User' => $data]]);
    }

    /**
     * Login as a username
     *
     * @param string $username Username to login.
     * @return void
     */
    public function loginAsUserName(string $username): void
    {
        $data = TableRegistry::getTableLocator()
            ->get(Configure::read('Users.table', 'Users'))->findByUsername($username)->first()->toArray();
        $this->session(['Auth' => ['User' => $data]]);
    }

    /**
     * @return bool
     */
    protected function _isVerboseOrDebug(): bool
    {
        return !empty(array_intersect(['--debug', '--verbose', '-v'], $_SERVER['argv']));
    }

    /**
     * Run permissions tests
     *
     * @param string $url Test url
     * @param string $username Username.
     * @param string $method Request method.
     * @param string $ajax Ajax value
     * @param string $responseCode Reponse code.
     * @param string $responseContains Text the response should contains, empty to expect an empty body.
     * @throws \PHPUnit\Exception
     */
    protected function _testPermissions(
        string $url,
        string $username,
        string $method,
        string $ajax,
        string $responseCode,
        string $responseContains = '',
    ): void {
        if ($this->_isVerboseOrDebug()) {
            (new ConsoleIo())->info(__(
                "\nUrl: {0} Username: {1} Method: {2} Ajax?: {3} Response Code: {4} Response Contains: {5} ",
                $url,
                $username,
                $method,
                $ajax,
                $responseCode,
                $responseContains,
            ), 0);
        }
        $this->loginAsUserName($username);
        if ($ajax === 'ajax') {
            $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        }
        if ($method === 'post') {
            $this->enableCsrfToken();
            $this->enableSecurityToken();
            $this->post($url);
        } else {
            $this->get($url);
        }
        if ($responseCode === '200') {
            $this->assertResponseOk();
        } else {
            $this->assertResponseCode((int)$responseCode);
        }

        if ($responseContains) {
            $this->assertResponseContains($responseContains);
        } else {
            $this->assertEmpty((string)$this->_response->getBody());
        }
    }

    /**
     * Test permissions
     *
     * The test class must define a static `provider()` method returning the CSV file names, for example
     * `[['admin.csv'], ['user.csv']]`. The files are read from `tests/Provider`.
     *
     * @param string $csv CSV name
     * @return void
     */
    #[DataProvider('provider')]
    public function testPermissions(string $csv): void
    {
        $path = $this->_permissionsCsvPath($csv);
        $this->assertFileExists($path);
        $rows = array_map(
            fn(string $line): array => str_getcsv($line, ',', '"', ''),
            (array)file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES),
        );
        foreach ($rows as $row) {
            if (!isset($row[0]) || trim($row[0]) === '' || $row[0][0] === '#') {
                continue;
            }
            [$url, $username, $method, $ajax, $responseCode, $responseContains] = array_map(
                'strval',
                array_pad($row, 6, ''),
            );
            $this->setUp();
            $this->_testPermissions($url, $username, $method, $ajax, $responseCode, $responseContains);
            $this->tearDown();
        }
    }

    /**
     * Path of a permissions CSV file
     *
     * @param string $csv CSV name
     * @return string
     */
    protected function _permissionsCsvPath(string $csv): string
    {
        return TESTS . 'Provider' . DS . $csv;
    }
}
