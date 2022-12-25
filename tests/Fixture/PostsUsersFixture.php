<?php
/**
 * Copyright 2010 - 2019, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010 - 2019, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Auth\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * PostUsers Fixture
 */
class PostsUsersFixture extends TestFixture
{
    /**
     * Records
     */
    public array $records = [
        [
            'id' => '00000000-0000-0000-0000-000000000011',
            'user_id' => '00000000-0000-0000-0000-000000000001',
            'post_id' => '00000000-0000-0000-0000-000000000001',
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000012',
            'user_id' => '00000000-0000-0000-0000-000000000002',
            'post_id' => '00000000-0000-0000-0000-000000000002',
        ],
    ];
}
