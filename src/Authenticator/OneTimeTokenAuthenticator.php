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
        if (is_array($token)) {
            $token = join($token);
        }

        if (!$token) {
            return new Result(null, Result::FAILURE_CREDENTIALS_MISSING);
        }

        $usersTable = TableRegistry::getTableLocator()->get(Configure::read('Users.table'));

        $user = $usersTable->loginWithToken($token);

        if (!$user) {
            return new Result(null, Result::FAILURE_CREDENTIALS_MISSING);
        }

        return new Result($user, Result::SUCCESS);
    }
}
