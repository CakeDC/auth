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

namespace CakeDC\Auth\Policy;

use Authorization\IdentityInterface;
use Authorization\Policy\Result;
use Authorization\Policy\ResultInterface;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class CollectionPolicy
 *
 * @package CakeDC\Auth\Policy
 */
class CollectionPolicy
{
    /**
     * List of policies
     *
     * @var array
     */
    protected array $policies;

    /**
     * CollectionPolicy constructor.
     *
     * @param array $policies List of policies.
     */
    public function __construct(array $policies)
    {
        $this->policies = $policies;
    }

    /**
     * Check permission, stop at first success from $policies or when all fails
     *
     * @param \Authorization\IdentityInterface|null $identity
     * @param \Psr\Http\Message\ServerRequestInterface $resource
     * @return \Authorization\Policy\ResultInterface
     */
    public function canAccess(?IdentityInterface $identity, ServerRequestInterface $resource): ResultInterface
    {
        $result = null;
        foreach ($this->policies as $policy => $config) {
            if (!is_array($config)) {
                $policy = $config;
                $config = [];
            }
            if (is_string($policy)) {
                $policy = new $policy($config);
            }
            assert($policy instanceof PolicyInterface);

            $result = $policy->canAccess($identity, $resource);
            if ($result->getStatus()) {
                return $this->afterResult($result);
            }
        }

        return $this->afterResult($result ?? new Result(false));
    }

    /**
     * @param \Authorization\Policy\ResultInterface $result
     * @return \Authorization\Policy\ResultInterface
     */
    protected function afterResult(ResultInterface $result): ResultInterface
    {
        if (Configure::read('CakeDC/Auth.DebugKit.PermissionPanel.enabled')) {
            $event = new Event('CakeDC/Auth.DebugKit.Permission.afterResult', $result);
            EventManager::instance()->dispatch($event);
        }

        return $result;
    }
}
