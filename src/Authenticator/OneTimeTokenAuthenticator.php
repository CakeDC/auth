<?php
declare(strict_types=1);

namespace CakeDC\Auth\Authenticator;

use Authentication\Authenticator\AbstractAuthenticator;
use Authentication\Authenticator\AuthenticatorInterface;
use Authentication\Authenticator\Result;
use Authentication\Authenticator\ResultInterface;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Psr\Http\Message\ServerRequestInterface;

class OneTimeTokenAuthenticator extends AbstractAuthenticator implements AuthenticatorInterface
{
    /**
     * @inheritDoc
     */
    public function authenticate(ServerRequestInterface $request): ResultInterface
    {
        /** @var \Cake\Http\ServerRequest $request */
        $token = $request->getQuery('token') ?: $request->getData('token');
        // The CakeDC/Users token form posts one input per character, `token[0]`, `token[1]`...
        if (is_array($token)) {
            $isStringList = array_filter($token, 'is_string') === $token;
            $token = $isStringList ? implode('', $token) : null;
        }

        if (!is_string($token) || $token === '') {
            return new Result(null, Result::FAILURE_CREDENTIALS_MISSING);
        }

        $usersTable = TableRegistry::getTableLocator()->get(Configure::read('Users.table'));

        /** @phpstan-ignore method.notFound */
        $user = $usersTable->loginWithToken($token);

        if (!$user) {
            return new Result(null, Result::FAILURE_CREDENTIALS_MISSING);
        }

        return new Result($user, Result::SUCCESS);
    }
}
