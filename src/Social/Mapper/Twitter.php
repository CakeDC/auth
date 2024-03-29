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

namespace CakeDC\Auth\Social\Mapper;

use Cake\Utility\Hash;

/**
 * Twitter Mapper
 */
class Twitter extends AbstractMapper
{
    /**
     * Url constants
     */
    public const TWITTER_BASE_URL = 'https://twitter.com/';

    /**
     * Map for provider fields
     *
     * @var array
     */
    protected array $_mapFields = [
        'id' => 'uid',
        'username' => 'nickname',
        'full_name' => 'name',
        'first_name' => 'firstName',
        'last_name' => 'lastName',
        'email' => 'email',
        'avatar' => 'imageUrl',
        'bio' => 'description',
        'validated' => 'validated',
    ];

    /**
     * Get link property value
     *
     * @param mixed $rawData raw data
     * @return string
     */
    protected function _link(mixed $rawData): string
    {
        return self::TWITTER_BASE_URL . Hash::get($rawData, $this->_mapFields['username']);
    }

    /**
     * Get avatar url
     *
     * @param mixed $rawData raw data
     * @return string
     */
    protected function _avatar(mixed $rawData): string
    {
        return str_replace('normal', 'bigger', Hash::get($rawData, $this->_mapFields['avatar']));
    }
}
