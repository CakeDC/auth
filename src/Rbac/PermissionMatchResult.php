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

namespace CakeDC\Auth\Rbac;

/**
 * Provides additional context on the result of a permission match operation,
 * for example allows to attach a debug reason on the matched rule
 *
 * @package Auth\Rbac
 */
class PermissionMatchResult
{
    /**
     * PermissionMatchResult constructor.
     *
     * @param bool $allowed rule was matched, allowed value
     * @param string $reason reason to either allow or deny
     * @param array|null $resource The resource url to check if allowed
     * @param array|null $permission The matching permission
     */
    public function __construct(
        protected bool $allowed = false,
        protected string $reason = '',
        protected ?array $resource = null,
        protected ?array $permission = null
    )
    {
    }

    /**
     * @param bool $allowed allowed value
     */
    public function setAllowed(bool $allowed): PermissionMatchResult
    {
        $this->allowed = $allowed;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAllowed(): bool
    {
        return $this->allowed;
    }

    /**
     * @param string $reason reason
     */
    public function setReason(string $reason): PermissionMatchResult
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * @return array|null
     */
    public function getPermission(): ?array
    {
        return $this->permission;
    }

    /**
     * @return array|null
     */
    public function getResource(): ?array
    {
        return $this->resource;
    }
}
