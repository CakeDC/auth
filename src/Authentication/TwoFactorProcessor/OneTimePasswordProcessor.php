<?php
declare(strict_types=1);

/**
 * Copyright 2010 - 2024, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010 - 2024, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace CakeDC\Auth\Authentication\TwoFactorProcessor;

use Authentication\Authenticator\Result;
use Authentication\Authenticator\ResultInterface;
use Cake\Core\Configure;
use CakeDC\Auth\Authentication\OneTimePasswordAuthenticationCheckerFactory;
use CakeDC\Auth\Authentication\TwoFactorProcessorInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * OneTimePasswordProcessor class
 */
class OneTimePasswordProcessor implements TwoFactorProcessorInterface
{
    public const NEED_TWO_FACTOR_VERIFY = 'NEED_TWO_FACTOR_VERIFY';

    public const TWO_FACTOR_VERIFY_SESSION_KEY = 'temporarySession';

    /**
     * Returns processor type.
     *
     * @return string
     */
    public function getType(): string
    {
        return self::NEED_TWO_FACTOR_VERIFY;
    }

    /**
     * Returns processor session key.
     *
     * @return string
     */
    public function getSessionKey(): string
    {
        return self::TWO_FACTOR_VERIFY_SESSION_KEY;
    }

    /**
     * Processor status detector.
     *
     * @return bool
     */
    public function enabled(): bool
    {
        return Configure::read('OneTimePasswordAuthenticator.login') !== false;
    }

    /**
     * Processor status detector.
     *
     * @return bool
     */
    public function isRequired(array $userData): bool
    {
        return $this->getOneTimePasswordAuthenticationChecker()->isRequired($userData);
    }

    /**
     * Proceed to 2fa processor after a valid result result.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request instance.
     * @param \Authentication\Authenticator\ResultInterface $result Input result object.
     * @return \Authentication\Authenticator\ResultInterface
     */
    public function proceed(ServerRequestInterface $request, ResultInterface $result): ResultInterface
    {
        /**
         * @var \Cake\Http\Session $session
         */
        $session = $request->getAttribute('session');
        $session->write($this->getSessionKey(), $result->getData());
        $result = new Result(null, $this->getType());

        return $result;
    }

    /**
     * Generates 2fa url, if enable.
     *
     * @param string $type Processor type.
     * @return array|null
     */
    public function getUrlByType(string $type): ?array
    {
        if ($type == $this->getType()) {
            return Configure::read('OneTimePasswordAuthenticator.verifyAction');
        }

        return null;
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
}
