<?php
declare(strict_types=1);

namespace CakeDC\Auth\Policy\Result;

use Authorization\Policy\ResultInterface;
use Psr\Http\Message\ServerRequestInterface;

class SuperuserResult implements ResultInterface
{
    /**
     * @param bool $status
     * @param \Psr\Http\Message\ServerRequestInterface $resource
     */
    public function __construct(protected bool $status, protected ServerRequestInterface $resource)
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
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public function getResource(): ServerRequestInterface
    {
        return $this->resource;
    }
}
