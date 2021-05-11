<?php
/*
 * Copyright 2010 - 2021, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010 - 2021, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Auth\Rbac\Rules;

use CakeDC\Auth\Rbac\Rules\Rule;

/**
 * Static rule registry to allow reusing rule instances in Rbac permissions
 */
class RuleRegistry
{
    /**
     * Rule instances array
     * @var array
     */
    protected static $rules = [];

    /**
     * Get a new Rule instance by class, construct a new instance if not found
     *
     * @param string $class
     * @param array|null $config
     * @return Rule
     */
    public static function get(string $class, ?array $config = []): Rule
    {
        if (!class_exists($class)) {
            throw new \BadMethodCallException(sprintf('Unknown rule class %s', $class));
        }
        if (!isset(self::$rules[$class])) {
            $ruleInstance = new $class($config);
            static::$rules[$class] = $ruleInstance;
        }

        return static::$rules[$class];
    }
}