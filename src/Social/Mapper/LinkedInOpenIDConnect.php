<?php
declare(strict_types=1);

/**
 * Copyright 2010 - 2026, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010 - 2026, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Auth\Social\Mapper;

use Cake\Utility\Hash;

/**
 * LinkedIn OpenID Connect Mapper
 */
class LinkedInOpenIDConnect extends AbstractMapper
{
    /**
     * Map for provider fields
     *
     * @var array
     */
    protected array $_mapFields = [
        'avatar' => 'picture',
        'first_name' => 'given_name',
        'last_name' => 'family_name',
        'email' => 'email',
        'link' => 'link',
        'id' => 'sub',
        'validated' => 'email_verified',
    ];

    /**
     * The id token reports the email verification status
     *
     * @param mixed $rawData raw data
     * @return bool
     */
    protected function _validated(mixed $rawData): bool
    {
        return (bool)Hash::get($rawData, $this->_mapFields['validated']);
    }

    /**
     * There is no way to retrieve the public profile url from the id token
     *
     * @return string
     */
    protected function _link(): string
    {
        return 'https://www.linkedin.com';
    }
}
