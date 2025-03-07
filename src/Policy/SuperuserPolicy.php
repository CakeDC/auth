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
use Authorization\Policy\ResultInterface;
use Cake\Core\InstanceConfigTrait;
use Cake\Http\ServerRequest;
use CakeDC\Auth\Policy\Result\SuperuserResult;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class RequestPolicy
 *
 * @package CakeDC\Auth\Policy
 */
class SuperuserPolicy implements PolicyInterface
{
    use InstanceConfigTrait;

    /**
     * default config
     *
     * @var array
     */
    protected array $_defaultConfig = [
        //superuser field in the Users table
        'superuser_field' => 'is_superuser',
    ];

    /**
     * RequestPolicy constructor.
     *
     * @param array $config policy configurations. Key superuser_field
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    /**
     * @inheritDoc
     */
    public function canAccess(?IdentityInterface $identity, ServerRequestInterface $resource): ResultInterface
    {
        $user = $identity !== null ? $identity->getOriginalData() : [];
        $superuserField = $this->getConfig('superuser_field');

        $isSuperUser = $user[$superuserField] ?? false;
        assert($resource instanceof ServerRequest);
        $params = $resource->getAttribute('params');
        $resourceData = [
            'prefix' => $params['prefix'] ?? null,
            'plugin' => $params['plugin'] ?? null,
            'extension' => $params['_ext'] ?? null,
            'controller' => $params['controller'] ?? null,
            'action' => $params['action'] ?? null,
        ];

        return new SuperuserResult($isSuperUser === true, $resourceData);
    }
}
