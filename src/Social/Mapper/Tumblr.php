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

use function Cake\Core\deprecationWarning;

/**
 * Tumblr Mapper
 *
 * @deprecated 11.1.0 The user id is a crc32 of the nickname, it can collide and changes when the blog is renamed.
 */
class Tumblr extends AbstractMapper
{
    /**
     * Constructor
     *
     * @param mixed $mapFields map fields
     */
    public function __construct(mixed $mapFields = null)
    {
        deprecationWarning(
            '11.1.0',
            'The Tumblr mapper is deprecated, its user id is a crc32 of the nickname, which ' .
            'can collide and changes when the blog is renamed.',
        );
        parent::__construct($mapFields);
    }

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
        'bio' => 'extra.blogs.0.description',
        'validated' => 'validated',
        'link' => 'extra.blogs.0.url',
    ];

    /**
     * Get id property value
     *
     * @param mixed $rawData raw data
     * @return string
     */
    protected function _id(mixed $rawData): string
    {
        return (string)crc32($rawData['nickname']);
    }
}
