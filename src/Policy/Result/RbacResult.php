<?php
declare(strict_types=1);

namespace CakeDC\Auth\Policy\Result;
use Authorization\Policy\ResultInterface;
use CakeDC\Auth\Rbac\PermissionMatchResult;

class RbacResult implements ResultInterface
{

    /**
     * @param \CakeDC\Auth\Rbac\PermissionMatchResult $permissionMatchResult
     */
    public function __construct(
        protected PermissionMatchResult $permissionMatchResult
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function getReason(): ?string
    {
        return $this->permissionMatchResult->getPermission()['message'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getStatus(): bool
    {
        return $this->permissionMatchResult->isAllowed();
    }

    /**
     * @return \CakeDC\Auth\Rbac\PermissionMatchResult
     */
    public function getPermissionMatchResult(): PermissionMatchResult
    {
        return $this->permissionMatchResult;
    }
}
