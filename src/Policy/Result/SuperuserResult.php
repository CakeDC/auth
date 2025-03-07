<?php
declare(strict_types=1);

namespace CakeDC\Auth\Policy\Result;

use Authorization\Policy\ResultInterface;
use Psr\Http\Message\ServerRequestInterface;

class SuperuserResult implements ResultInterface
{
    /**
     * @param bool $status
     * @param array $resource
     */
    public function __construct(protected bool $status, protected array $resource)
    {
    }

    /**
     * @inheritDoc
     */
    public function getReason(): ?string
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getStatus(): bool
    {
        return $this->status;
    }

    /**
     * @return array
     */
    public function getResource(): array
    {
        return $this->resource;
    }
}
