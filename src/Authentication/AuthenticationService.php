<?php
declare(strict_types=1);

/**
 * Copyright 2010 - 2019, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010 - 2019, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Auth\Authentication;

use Authentication\AuthenticationService as BaseService;
use Authentication\Authenticator\Result;
use Authentication\Authenticator\ResultInterface;
use Authentication\Authenticator\StatelessInterface;
use Cake\Datasource\EntityInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

class AuthenticationService extends BaseService
{
    public const NEED_TWO_FACTOR_VERIFY = 'NEED_TWO_FACTOR_VERIFY';

    public const TWO_FACTOR_VERIFY_SESSION_KEY = 'temporarySession';

    public const NEED_WEBAUTHN_2FA_VERIFY = 'NEED_WEBAUTHN2FA_VERIFY';

    public const WEBAUTHN_2FA_SESSION_KEY = 'Webauthn2fa.User';

    /**
     * All failures authenticators
     *
     * @var array<\CakeDC\Auth\Authentication\Failure>
     */
    protected array $failures = [];

    /**
     * Proceed to google verify action after a valid result result
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Authentication\Authenticator\ResultInterface $result The original result
     * @return \Authentication\Authenticator\ResultInterface The result object.
     */
    protected function proceedToGoogleVerify(ServerRequestInterface $request, ResultInterface $result): ResultInterface
    {
        /**
         * @var \Cake\Http\Session $session
         */
        $session = $request->getAttribute('session');
        $session->write(self::TWO_FACTOR_VERIFY_SESSION_KEY, $result->getData());
        $result = new Result(null, self::NEED_TWO_FACTOR_VERIFY);
        $this->_successfulAuthenticator = null;

        return $this->_result = $result;
    }

    /**
     * Proceed to webauthn2fa flow after a valid result result
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request response to manipulate
     * @param \Authentication\Authenticator\ResultInterface $result valid result
     * @return \Authentication\Authenticator\ResultInterface with result, request and response keys
     */
    protected function proceedToWebauthn2fa(ServerRequestInterface $request, ResultInterface $result): ResultInterface
    {
        /**
         * @var \Cake\Http\Session $session
         */
        $session = $request->getAttribute('session');
        $session->write(self::WEBAUTHN_2FA_SESSION_KEY, $result->getData());
        $result = new Result(null, self::NEED_WEBAUTHN_2FA_VERIFY);
        $this->_successfulAuthenticator = null;

        return $this->_result = $result;
    }

    /**
     * Get the configured one-time password authentication checker
     *
     * @return \CakeDC\Auth\Authentication\OneTimePasswordAuthenticationCheckerInterface
     */
    protected function getOneTimePasswordAuthenticationChecker(): OneTimePasswordAuthenticationCheckerInterface
    {
        return (new OneTimePasswordAuthenticationCheckerFactory())->build();
    }

    /**
     * Get the configured Webauthn authentication checker
     *
     * @return \CakeDC\Auth\Authentication\Webauthn2fAuthenticationCheckerInterface
     */
    protected function getWebauthn2fAuthenticationChecker(): Webauthn2fAuthenticationCheckerInterface
    {
        return (new Webauthn2fAuthenticationCheckerFactory())->build();
    }

    /**
     * {@inheritDoc}
     *
     * @throws \RuntimeException Throws a runtime exception when no authenticators are loaded.
     */
    public function authenticate(ServerRequestInterface $request): ResultInterface
    {
        if ($this->authenticators()->isEmpty()) {
            throw new RuntimeException(
                'No authenticators loaded. You need to load at least one authenticator.'
            );
        }

        $result = null;
        /** @var \Authentication\Authenticator\AbstractAuthenticator $authenticator */
        foreach ($this->authenticators() as $authenticator) {
            $result = $authenticator->authenticate($request);
            if ($result->isValid()) {
                $skipTwoFactorVerify = $authenticator->getConfig('skipTwoFactorVerify');
                $userData = $result->getData();
                if ($userData instanceof EntityInterface) {
                    $userData = $userData->toArray();
                }
                $webauthn2faChecker = $this->getWebauthn2fAuthenticationChecker();
                if ($skipTwoFactorVerify !== true && $webauthn2faChecker->isRequired($userData)) {
                    return $this->proceedToWebauthn2fa($request, $result);
                }

                $otpCheck = $this->getOneTimePasswordAuthenticationChecker();
                if ($skipTwoFactorVerify !== true && $otpCheck->isRequired($userData)) {
                    return $this->proceedToGoogleVerify($request, $result);
                }

                $this->_successfulAuthenticator = $authenticator;
                $this->_result = $result;

                return $this->_result = $result;
            } else {
                $this->failures[] = new Failure($authenticator, $result);
            }

            if ($authenticator instanceof StatelessInterface) {
                $authenticator->unauthorizedChallenge($request);
            }
        }

        if ($result === null) {
            $result = new Result(null, ResultInterface::FAILURE_OTHER);
        }

        $this->_successfulAuthenticator = null;

        return $this->_result = $result;
    }

    /**
     * Get list the list of failures processed
     *
     * @return array<\CakeDC\Auth\Authentication\Failure>
     */
    public function getFailures(): array
    {
        return $this->failures;
    }
}
