<?php
/**
 * Copyright 2010 - 2019, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010 - 2018, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

use Authentication\Identifier\IdentifierCollection;
use CakeDC\Auth\Authenticator\CookieAuthenticator;
use Cake\Http\Response;
use Cake\Http\ServerRequestFactory;
use Cake\TestSuite\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class CookieAuthenticatorTest extends TestCase
{
    public function dataProviderPersistIdentity()
    {
        return [
            [true, 'remember_me', ['remember_me' => 1], []],
            [true, 'remember_me', ['remember_me' => 1], ['remember_me' => 0]],
            [true, 'remember_me', [], ['remember_me' => 1]],
            [true, 'my_remember', ['my_remember' => 1], []],
            [true, 'my_remember', ['my_remember' => 1], ['remember_me' => 0]],
            [true, 'my_remember', [], ['my_remember' => 1]],
            [false, 'remember_me', [], []],
            [false, 'remember_me', ['user_me' => 1], ['remember_me' => 1]],
            [false, 'remember_me', ['user_me' => 1], []],
            [false, 'my_remember', ['remember_me' => 1], []],
            [false, 'my_remember', ['remember_me' => 1], ['remember_me' => 0]],
            [false, 'my_remember', [], ['remember_me' => 1]],
        ];
    }

    /**
     * testPersistIdentity
     *
     * @param bool $setCookie Will set cookie
     * @param string $field Remember me field.
     * @param array $post Post data
     * @param array $session Session data for CookieAuth key
     *
     * @dataProvider dataProviderPersistIdentity
     * @return void
     */
    public function testPersistIdentity($setCookie, $field, array $post, array $session)
    {
        $identifiers = new IdentifierCollection([
            'Authentication.Password'
        ]);
        $request = new \Cake\Http\ServerRequest();
        $uri = new \Zend\Diactoros\Uri('/login');
        $uri->base = null;
        $request = $request->withUri($uri);

        $request = $request->withParsedBody($post);
        $request->getSession()->write('CookieAuth', $session);
        $response = new Response();
        $authenticator = new CookieAuthenticator($identifiers, [
            'loginUrl' => '/login',
            'rememberMeField' => $field
        ]);
        $identity = new ArrayObject([
            'username' => 'johndoe',
            'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO'
        ]);
        $result = $authenticator->persistIdentity($request, $response, $identity);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('request', $result);
        $this->assertArrayHasKey('response', $result);
        $this->assertInstanceOf(RequestInterface::class, $result['request']);
        $this->assertInstanceOf(ResponseInterface::class, $result['response']);
        if ($setCookie) {
            $this->assertContains('CookieAuth=%5B%22johndoe%22%2C%22%242y%2410%24', $result['response']->getHeaderLine('Set-Cookie'));
        } else {
            $this->assertNotContains('CookieAuth', $result['response']->getHeaderLine('Set-Cookie'));
        }
    }
}
