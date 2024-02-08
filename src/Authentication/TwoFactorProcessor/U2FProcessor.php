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
use CakeDC\Auth\Authentication\U2fAuthenticationCheckerFactory;
use Psr\Http\Message\ServerRequestInterface;

/**
 * U2FProcessor class
 */
class U2FProcessor implements TwoFactorProcessorInterface
{
    public const U2F_SESSION_KEY = 'U2f.User';

    public const NEED_U2F_VERIFY = 'NEED_U2F_VERIFY';

    /**
     * Returns processor type.
     *
     * @return string
     */
    public function getType(): string
    {
        return self::NEED_U2F_VERIFY;
    }

    /**
     * Returns processor session key.
     *
     * @return string
     */
    public function getSessionKey(): string
    {
        return self::U2F_SESSION_KEY;
    }

    /**
     * Processor status detector.
     *
     * @return bool
     */
    public function enabled(): bool
    {
        $u2fEnabled = Configure::read('U2f.enabled') !== false;
        if ($u2fEnabled) {
            trigger_error(Plugin::DEPRECATED_MESSAGE_U2F, E_USER_DEPRECATED);
        }

        return $u2fEnabled;
    }

    /**
     * Processor status detector.
     *
     * @return bool
     */
    public function isRequired(array $userData): bool
    {
        return $this->getU2fAuthenticationChecker()->isRequired($userData);
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
            return Configure::read('U2f.startAction');
        }

        return null;
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
}
