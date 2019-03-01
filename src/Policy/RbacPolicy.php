<?php
/**
 * Copyright 2010 - 2019, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010 - 2018, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Auth\Policy;

use CakeDC\Auth\Rbac\Rbac;
use Cake\Core\InstanceConfigTrait;
use Psr\Http\Message\ServerRequestInterface;

class RbacPolicy
{
    use InstanceConfigTrait;

    protected $_defaultConfig = [
        'adapter' => [
            'className' => Rbac::class
        ]
    ];

    /**
     * RbacPolicy constructor.
     *
     * @param array $config Policy configurations
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    /**
     * Check rbac permission
     *
     * @param \Authorization\IdentityInterface|null $identity user identity
     * @param ServerRequestInterface $resource server request
     * @return bool
     */
    public function canAccess($identity, $resource)
    {
        $rbac = $this->getRbac($resource);

        $user = $identity ? $identity->getOriginalData()->toArray() : [];

        return (bool)$rbac->checkPermissions($user, $resource);
    }

    /**
     * Get the rbac object from source or create a new one
     *
     * @param ServerRequestInterface $resource server request
     * @return Rbac
     */
    public function getRbac($resource)
    {
        $rbac = $resource->getAttribute('rbac');
        if ($rbac !== null) {
            return $rbac;
        }
        $adapter = $this->getConfig('adapter');
        if (is_object($adapter)) {
            return $adapter;
        }

        return $this->createRbac($adapter);
    }

    /**
     * Create an instance of Rbac
     *
     * @param array $config Rbac config
     *
     * @return \CakeDC\Auth\Rbac\Rbac
     */
    protected function createRbac($config)
    {
        if (isset($config['className'])) {
            $className = $config['className'];
            unset($config['className']);

            return new $className($config);
        }

        throw new \InvalidArgumentException('Config "adapter" should be an object or an array with key className');
    }
}
