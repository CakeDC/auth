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

namespace CakeDC\Auth\Authentication;

use Authentication\AuthenticationService as BaseService;
use Authentication\Authenticator\Result;
use Authentication\Authenticator\ResultInterface;
use Authentication\Authenticator\StatelessInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

class AuthenticationService extends BaseService
{
    const NEED_TWO_FACTOR_VERIFY = 'NEED_TWO_FACTOR_VERIFY';

    const TWO_FACTOR_VERIFY_SESSION_KEY = 'temporarySession';

    const U2F_SESSION_KEY = 'U2f.User';

    const NEED_U2F_VERIFY = 'NEED_U2F_VERIFY';
    /**
     * All failures authenticators
     *
     * @var Failure[]
     */
    protected $failures = [];
    /**
     * Proceed to google verify action after a valid result result
     *
     * @param ServerRequestInterface $request response to manipulate
     * @param ResponseInterface $response base response to manipulate
     * @param ResultInterface $result valid result
     * @return array with result, request and response keys
     */
    protected function proceedToGoogleVerify(ServerRequestInterface $request, ResponseInterface $response, ResultInterface $result)
    {
        $request->getSession()->write(self::TWO_FACTOR_VERIFY_SESSION_KEY, $result->getData());

        $result = new Result(null, self::NEED_TWO_FACTOR_VERIFY);

        $this->_successfulAuthenticator = null;
        $this->_result = $result;

        return compact('result', 'request', 'response');
    }

    /**
     * Proceed to U2f flow after a valid result result
     *
     * @param ServerRequestInterface $request response to manipulate
     * @param ResponseInterface $response base response to manipulate
     * @param ResultInterface $result valid result
     * @return array with result, request and response keys
     */
    protected function proceedToU2f(ServerRequestInterface $request, ResponseInterface $response, ResultInterface $result)
    {
        $request->getSession()->write(self::U2F_SESSION_KEY, $result->getData());

        $result = new Result(null, self::NEED_U2F_VERIFY);

        $this->_successfulAuthenticator = null;
        $this->_result = $result;

        return compact('result', 'request', 'response');
    }

    /**
     * Get the configured one-time password authentication checker
     *
     * @return \CakeDC\Auth\Authentication\OneTimePasswordAuthenticationCheckerInterface
     */
    protected function getOneTimePasswordAuthenticationChecker()
    {
        return (new OneTimePasswordAuthenticationCheckerFactory())->build();
    }

    /**
     * Get the configured u2f authentication checker
     *
     * @return \CakeDC\Auth\Authentication\U2fAuthenticationCheckerInterface
     */
    protected function getU2fAuthenticationChecker()
    {
        return (new U2fAuthenticationCheckerFactory())->build();
    }

    /**
     * {@inheritDoc}
     *
     * @throws \RuntimeException Throws a runtime exception when no authenticators are loaded.
     */
    public function authenticate(ServerRequestInterface $request, ResponseInterface $response)
    {
        if ($this->authenticators()->isEmpty()) {
            throw new RuntimeException(
                'No authenticators loaded. You need to load at least one authenticator.'
            );
        }


        $this->failures = [];
        $result = null;
        foreach ($this->authenticators() as $authenticator) {
            $result = $authenticator->authenticate($request, $response);
            if ($result->isValid()) {
                $skipTwoFactorVerify = $authenticator->getConfig('skipTwoFactorVerify');
                $userData = $result->getData()->toArray();
                $u2fCheck = $this->getU2fAuthenticationChecker();
                if ($skipTwoFactorVerify !== true && $u2fCheck->isRequired($userData)) {
                    return $this->proceedToU2f($request, $response, $result);
                }

                $otpCheck = $this->getOneTimePasswordAuthenticationChecker();
                if ($skipTwoFactorVerify !== true && $otpCheck->isRequired($userData)) {
                    return $this->proceedToGoogleVerify($request, $response, $result);
                }

                if (!($authenticator instanceof StatelessInterface)) {
                    $requestResponse = $this->persistIdentity($request, $response, $result->getData());
                    $request = $requestResponse['request'];
                    $response = $requestResponse['response'];
                }

                $this->_successfulAuthenticator = $authenticator;
                $this->_result = $result;

                return [
                    'result' => $result,
                    'request' => $request,
                    'response' => $response
                ];
            } else {
                $this->failures[] = new Failure($authenticator, $result);
            }

            if (!$result->isValid() && $authenticator instanceof StatelessInterface) {
                $authenticator->unauthorizedChallenge($request);
            }
        }

        $this->_successfulAuthenticator = null;
        $this->_result = $result;

        return [
            'result' => $result,
            'request' => $request,
            'response' => $response
        ];
    }

    /**
     * Get list the list of failures processed
     *
     * @return Failure[]
     */
    public function getFailures()
    {
        return $this->failures;
    }
}
