<?php
declare(strict_types=1);
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
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

class AuthenticationService extends BaseService
{
    public const NEED_TWO_FACTOR_VERIFY = 'NEED_TWO_FACTOR_VERIFY';

    public const TWO_FACTOR_VERIFY_SESSION_KEY = 'temporarySession';

    /**
     * All failures authenticators
     *
     * @var \CakeDC\Auth\Authentication\Failure[]
     */
    protected $failures = [];
    /**
     * Proceed to google verify action after a valid result result
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request response to manipulate
     * @param \Authentication\Authenticator\ResultInterface $result valid result
     * @return array with result, request and response keys
     */
    protected function proceedToGoogleVerify(ServerRequestInterface $request, /*ResponseInterface $response, */ResultInterface $result)
    {
        $request->getSession()->write(self::TWO_FACTOR_VERIFY_SESSION_KEY, $result->getData());

        $result = new Result(null, self::NEED_TWO_FACTOR_VERIFY);

        $this->_successfulAuthenticator = null;
        $this->_result = $result;

        return compact('result', 'request'/*, 'response'*/);
    }

    /**
     * Get the configured two factory authentication
     *
     * @return \CakeDC\Auth\Authentication\TwoFactorAuthenticationCheckerInterface
     */
    protected function getTwoFactorAuthenticationChecker()
    {
        return (new TwoFactorAuthenticationCheckerFactory())->build();
    }

    /**
     * {@inheritDoc}
     *
     * @throws \RuntimeException Throws a runtime exception when no authenticators are loaded.
     */
    public function authenticate(ServerRequestInterface $request)
    {
        if ($this->authenticators()->isEmpty()) {
            throw new RuntimeException(
                'No authenticators loaded. You need to load at least one authenticator.'
            );
        }

        $twoFaCheck = $this->getTwoFactorAuthenticationChecker();
        $this->failures = [];
        $result = null;
        foreach ($this->authenticators() as $authenticator) {
            $result = $authenticator->authenticate($request);

            if ($result->isValid()) {
                $twoFaRequired = $twoFaCheck->isRequired($result->getData()->toArray());
                if ($twoFaRequired && $authenticator->getConfig('skipTwoFactorVerify') !== true) {
                    return $this->proceedToGoogleVerify($request, /*$response, */$result);
                }

/*                if (!($authenticator instanceof StatelessInterface)) {
                    $requestResponse = $this->persistIdentity($request, $response, $result->getData());
                    $request = $requestResponse['request'];
                    $response = $requestResponse['response'];
                }*/

                $this->_successfulAuthenticator = $authenticator;
                $this->_result = $result;

                return [
                    'result' => $result,
                    'request' => $request,
//                    'response' => $response
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
//            'response' => $response
        ];
    }

    /**
     * Get list the list of failures processed
     *
     * @return \CakeDC\Auth\Authentication\Failure[]
     */
    public function getFailures()
    {
        return $this->failures;
    }
}
