<?php
namespace CakeDC\Auth\Test;

use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

trait BaseTraitTest
{
    /**
     * Sets up the session as a logged in user for an user with id $id
     *
     * @param $id
     * @return void
     */
    public function loginAsUserId($id)
    {
        $data = TableRegistry::getTableLocator()
            ->get(Configure::read('Users.table', 'Users'))->get($id)->toArray();
        $this->session(['Auth' => ['User' => $data]]);
    }

    /**
     * Login as a username
     *
     * @param $username
     * @return void
     */
    public function loginAsUserName($username)
    {
        $data = TableRegistry::getTableLocator()
            ->get(Configure::read('Users.table', 'Users'))->findByUsername($username)->first()->toArray();
        $this->session(['Auth' => ['User' => $data]]);
    }

    /**
     * @return bool
     */
    protected function _isVerboseOrDebug()
    {
        return !empty(array_intersect(['--debug', '--verbose', '-v'], $_SERVER['argv']));
    }

    /**
     * @param $url
     * @param $username
     * @param $method
     * @param $ajax
     * @param $responseCode
     * @param $responseContains
     * @throws \PHPUnit\Exception
     */
    protected function _testPermissions($url, $username, $method, $ajax, $responseCode, $responseContains)
    {

       if ($this->_isVerboseOrDebug()) {
            (new ConsoleIo())->info(__(
                "\nUrl: {0} Username: {1} Method: {2} Ajax?: {3} Response Code: {4} Response Contains: {5} ",
                $url, $username, $method, $ajax, $responseCode, $responseContains
            ), 0);
            die();
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
            $this->assertResponseCode($responseCode);
        }

        if ($responseContains) {
            $this->assertResponseContains($responseContains);
        } else {
            $this->assertEmpty((string)$this->_response->getBody());
        }
    }

    /**
     * @param $csv
     * @return array
     * @dataProvider provider
     */
    public function testPermissions($csv)
    {
        $this->assertTrue(file_exists(TESTS . 'Provider' . DS . $csv));
        $rows = array_map('str_getcsv', file(TESTS . 'Provider' . DS . $csv));
        foreach ($rows as $row) {
            if ($row[0][0] === '#') {
                continue;
            }
            list($url, $username, $method, $ajax, $responseCode, $responseContains) = array_pad($row, 6, null);
            $this->setUp();
            $this->_testPermissions($url, $username, $method, $ajax, $responseCode, $responseContains);
            $this->tearDown();
        }

    }
}
