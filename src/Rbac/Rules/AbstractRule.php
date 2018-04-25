<?php
/**
 * Copyright 2010 - 2017, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010 - 2017, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace CakeDC\Auth\Rbac\Rules;

use Cake\Core\InstanceConfigTrait;
use Cake\Datasource\ModelAwareTrait;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use OutOfBoundsException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class AbstractRule
 * @package CakeDC\Auth\Auth\Rules
 */
abstract class AbstractRule implements Rule
{
    use InstanceConfigTrait;
    use LocatorAwareTrait;
    use ModelAwareTrait;

    /**
     * @var array default config
     */
    protected $_defaultConfig = [];

    /**
     * AbstractRule constructor.
     * @param array $config Rule config
     */
    public function __construct($config = [])
    {
        $this->setConfig($config);
    }

    /**
     * Get a table from the alias, table object or inspecting the request for a default table
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request request
     * @param mixed $table table
     * @return \Cake\Datasource\RepositoryInterface
     */
    protected function _getTable(ServerRequestInterface $request, $table = null)
    {
        if (empty($table)) {
            return $this->_getTableFromRequest($request);
        }
        if ($table instanceof Table) {
            return $table;
        }

        return TableRegistry::get($table);
    }

    /**
     * Inspect the request and try to retrieve a table based on the current controller
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request request
     * @return \Cake\Datasource\RepositoryInterface
     * @throws \OutOfBoundsException if table alias can't be extracted from request
     */
    protected function _getTableFromRequest(ServerRequestInterface $request)
    {
        $params = $request->getAttribute('params');

        $plugin = Hash::get($params, 'plugin');
        $controller = Hash::get($params, 'controller');
        $modelClass = ($plugin ? $plugin . '.' : '') . $controller;

        $this->modelFactory('Table', [$this->getTableLocator(), 'get']);
        if (empty($modelClass)) {
            throw new OutOfBoundsException('Missing Table alias, we could not extract a default table from the request');
        }

        return $this->loadModel($modelClass);
    }

    /**
     * Check the current entity is owned by the logged in user
     *
     * @param array $user Auth array with the logged in data
     * @param string $role role of the user
     * @param \Psr\Http\Message\ServerRequestInterface $request current request, used to get a default table if not provided
     * @return bool
     * @throws \OutOfBoundsException if table is not found or it doesn't have the expected fields
     */
    abstract public function allowed(array $user, $role, ServerRequestInterface $request);
}
