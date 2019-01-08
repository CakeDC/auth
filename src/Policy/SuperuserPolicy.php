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
use Cake\Core\InstanceConfigTrait;
use Cake\Utility\Hash;

/**
 * Class RequestPolicy
 *
 * @package CakeDC\Auth\Policy
 */
class SuperuserPolicy
{
    use InstanceConfigTrait;
    /**
     * default config
     *
     * @var array
     */
    protected $_defaultConfig = [
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
     * Check permission
     *
     * @param \Authorization\IdentityInterface|null $identity user identity
     *
     * @return bool
     */
    public function canAccess($identity)
    {
        $user = $identity ? $identity->getOriginalData()->toArray() : [];
        $superuserField = $this->getConfig('superuser_field');
        if (Hash::check($user, $superuserField)) {
            return (bool)Hash::get($user, $superuserField);
        }

        return false;
    }
}