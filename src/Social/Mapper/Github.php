<?php
declare(strict_types=1);

/**
 * Copyright 2010 - 2025, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010 - 2025, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Auth\Social\Mapper;

use Cake\Utility\Hash;

/**
 * Github Mapper
 */
class Github extends AbstractMapper
{
    /**
     * Map for provider fields
     *
     * @var array
     */
    protected array $_mapFields = [
        'username' => 'login',
        'full_name' => 'name',
        'avatar' => 'avatar_url',
        'link' => 'html_url',
        'locale' => 'location',
    ];
}
