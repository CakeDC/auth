<?php
declare(strict_types=1);
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

use Cake\Core\InstanceConfigTrait;
use CakeDC\Auth\Rbac\Rbac;

class RbacPolicy
{
    use InstanceConfigTrait;

    /**
     * The default config.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'adapter' => [
            'className' => Rbac::class,
        ],
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
     * @param \Psr\Http\Message\ServerRequestInterface $resource server request
     * @return bool
     */
    public function canAccess($identity, $resource): bool
    {
        $rbac = $this->getRbac($resource);

        $user = $identity ? $identity->getOriginalData() : [];

        return (bool)$rbac->checkPermissions($user, $resource);
    }

    /**
     * Get the rbac object from source or create a new one
     *
     * @param \Psr\Http\Message\ServerRequestInterface $resource server request
     * @return \CakeDC\Auth\Rbac\Rbac
     */
    public function getRbac($resource): Rbac
    {
        $rbac = $resource->getAttribute('rbac');
        if ($rbac !== null) {
            return $rbac;
        }
        $adapter = $this->getConfig('adapter');
        if (is_array($adapter)) {
            return $this->createRbac($adapter);
        }

        return $adapter;
    }

    /**
     * Create an instance of Rbac
     *
     * @param array $config Rbac config
     *
     * @throws \InvalidArgumentException When 'key' className is missing in $config
     * @return \CakeDC\Auth\Rbac\Rbac
     */
    protected function createRbac($config): Rbac
    {
        if (isset($config['className'])) {
            $className = $config['className'];
            unset($config['className']);

            $rbac = new $className($config);
            if ($rbac instanceof Rbac) {
                return $rbac;
            }
        }

        throw new \InvalidArgumentException('Config "adapter" should be an object or an array with key className');
    }
}
