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
namespace CakeDC\Auth\Authentication;

use Authentication\Authenticator\ResultInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * TwoFactorProcessor Interface
 */
interface TwoFactorProcessorInterface
{
    /**
     * Processor status detector.
     *
     * @return bool
     */
    public function enabled(): bool;

    /**
     * Processor status detector.
     *
     * @return bool
     */
    public function isRequired(array $userData): bool;

    /**
     * Proceed to 2fa processor after a valid result result.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request instance.
     * @param \Authentication\Authenticator\ResultInterface $result Input result object.
     * @return \Authentication\Authenticator\ResultInterface
     */
    public function proceed(ServerRequestInterface $request, ResultInterface $result): ResultInterface;

    /**
     * Generates 2fa url, if enable.
     *
     * @param string $type Processor type.
     * @return array|null
     */
    public function getUrlByType(string $type): ?array;
}
