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

use CakeDC\Auth\Social\Mapper\AbstractMapper;

class LinkedInOpenIDConnect extends AbstractMapper
{
    /**
     * Map for provider fields
     *
     * @var array
     */
    protected $_mapFields = [
        'avatar' => 'picture',
        'first_name' => 'given_name',
        'last_name' => 'family_name',
        'email' => 'email',
        'link' => 'link',
        'id' => 'sub',
    ];

    protected function _link(): string {
        // no way to retrieve the public url from the users profile

        return 'https://www.linkedin.com';
    }
}
