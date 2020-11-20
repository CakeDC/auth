<?php


namespace CakeDC\Auth\Test\App\Auth\Rule;


use CakeDC\Auth\Rbac\Rules\AbstractRule;
use Psr\Http\Message\ServerRequestInterface;

class SampleRule extends AbstractRule
{

    /**
     * @inheritDoc
     */
    public function allowed($user, $role, ServerRequestInterface $request)
    {
        return true;
    }
}
