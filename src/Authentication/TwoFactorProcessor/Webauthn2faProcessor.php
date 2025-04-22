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
use CakeDC\Auth\Authentication\TwoFactorProcessorInterface;
use CakeDC\Auth\Authentication\Webauthn2fAuthenticationCheckerFactory;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Webauthn2faProcessor class
 */
class Webauthn2faProcessor implements TwoFactorProcessorInterface
{
    public const NEED_WEBAUTHN_2FA_VERIFY = 'NEED_WEBAUTHN2FA_VERIFY';

    public const WEBAUTHN_2FA_SESSION_KEY = 'Webauthn2fa.User';

    /**
     * Returns processor type.
     *
     * @return string
     */
    public function getType(): string
    {
        return self::NEED_WEBAUTHN_2FA_VERIFY;
    }

    /**
     * Returns processor session key.
     *
     * @return string
     */
    public function getSessionKey(): string
    {
        return self::WEBAUTHN_2FA_SESSION_KEY;
    }

    /**
     * Processor status detector.
     *
     * @return bool
     */
    public function enabled(): bool
    {
        return Configure::read('Webauthn2fa.enabled') !== false;
    }

    /**
     * Processor status detector.
     *
     * @return bool
     */
    public function isRequired(array $userData): bool
    {
        return $this->getWebauthn2fAuthenticationChecker()->isRequired($userData);
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
            return Configure::read('Webauthn2fa.startAction');
        }

        return null;
    }

    /**
     * Get the configured u2f authentication checker
     *
     * @return \CakeDC\Auth\Authentication\Webauthn2fAuthenticationCheckerInterface
     */
    protected function getWebauthn2fAuthenticationChecker()
    {
        return (new Webauthn2fAuthenticationCheckerFactory())->build();
    }
}
