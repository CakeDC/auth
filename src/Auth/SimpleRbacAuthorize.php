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

namespace CakeDC\Auth\Auth;

use CakeDC\Auth\Rbac\Rbac;
use Cake\Auth\BaseAuthorize;
use Cake\Controller\ComponentRegistry;
use Cake\Http\ServerRequest;
use Cake\Log\LogTrait;

/**
 * Simple Rbac Authorize
 *
 * Matches current plugin/controller/action against defined permissions in permissions.php file
 */
class SimpleRbacAuthorize extends BaseAuthorize
{
    use LogTrait;

    /**
     * @var array configuration is passed to the Rbac object instance for convenience setup from Auth
     */
    protected $_defaultConfig = [
        // autoload permissions.php passed to Rbac object config
        'autoload_config' => 'permissions',
        // role field in the Users table  passed to Rbac object config
        'role_field' => 'role',
        // default role, used in new users registered and also as role matcher when no role is available
        // passed to Rbac object config
        'default_role' => 'user',
        /*
         * This is a quick roles-permissions implementation
         * Rules are evaluated top-down, first matching rule will apply
         * Each line define
         *      [
         *          'role' => 'admin',
         *          'plugin', (optional, default = null)
         *          'prefix', (optional, default = null)
         *          'extension', (optional, default = null)
         *          'controller',
         *          'action',
         *          'allowed' (optional, default = true)
         *      ]
         * You could use '*' to match anything
         * You could use [] to match an array of options, example 'role' => ['adm1', 'adm2']
         * You could use a callback in your 'allowed' to process complex authentication, like
         *   - ownership
         *   - permissions stored in your database
         *   - permission based on an external service API call
         * You could use an instance of the \CakeDC\Auth\Auth\Rules\Rule interface to reuse your custom rules
         *
         * Examples:
         * 1. Callback to allow users editing their own Posts:
         *
         * 'allowed' => function (array $user, $role, Request $request) {
         *       $postId = Hash::get($request->params, 'pass.0');
         *       $post = TableRegistry::get('Posts')->get($postId);
         *       $userId = Hash::get($user, 'id');
         *       if (!empty($post->user_id) && !empty($userId)) {
         *           return $post->user_id === $userId;
         *       }
         *       return false;
         *   }
         * 2. Using the Owner Rule
         * 'allowed' => new Owner() //will pick by default the post id from the first pass param
         *
         * Check the Owner Rule docs for more details
         *
         *
         */
        'permissions' => [],
    ];

    /**
     * @var Rbac
     */
    protected $rbac;

    /**
     * Autoload permission configuration
     *
     * @param ComponentRegistry $registry component registry
     * @param array $config config
     */
    public function __construct(ComponentRegistry $registry, array $config = [])
    {
        parent::__construct($registry, $config);
        $this->rbac = $this->rbacInstance($config);
    }

    /**
     * Rbac instance for mocking
     *
     * @param array $config configuration
     * @return Rbac
     */
    protected function rbacInstance($config = [])
    {
        return new Rbac($config);
    }

    /**
     * Match the current plugin/controller/action against loaded permissions
     * Set a default role if no role is provided
     *
     * @param array $user user data
     * @param \Cake\Http\ServerRequest $request request
     * @return bool
     */
    public function authorize($user, ServerRequest $request)
    {
        $allowed = $this->rbac->checkPermissions($user, $request);

        return $allowed;
    }
}
