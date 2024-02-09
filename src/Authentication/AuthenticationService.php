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
     * Proceed to 2fa processor after a valid result result
     *
     * @param \CakeDC\Auth\Authentication\TwoFactorProcessorInterface $processor The processor.
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Authentication\Authenticator\ResultInterface $result The original result
     * @return \Authentication\Authenticator\ResultInterface The result object.
     */
    protected function proceed2FA(TwoFactorProcessorInterface $processor, ServerRequestInterface $request, ResultInterface $result): ResultInterface
    {
        $result = $processor->proceed($request, $result);
        $this->_successfulAuthenticator = null;

        return $this->_result = $result;
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
        $processors = $this->getConfig('processors');
        foreach ($this->authenticators() as $authenticator) {
            $result = $authenticator->authenticate($request);
            if ($result->isValid()) {
                $skipTwoFactorVerify = $authenticator->getConfig('skipTwoFactorVerify');
                $userData = $result->getData();
                if ($userData instanceof EntityInterface) {
                    $userData = $userData->toArray();
                }
                foreach ($processors as $processor) {
                    if ($skipTwoFactorVerify !== true && $processor->isRequired($userData)) {
                        return $this->proceed2FA($processor, $request, $result);
                    }
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
