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

class Azure extends AbstractMapper
{
    /**
     * Map for provider fields
     *
     * @var array
     */
    protected array $_mapFields = [
        'id' => 'sub',
        'full_name' => 'name',
        'username' => 'unique_name',
        'email' => 'upn',
    ];

    /**
     * Get link property value
     *
     * @param mixed $rawData raw data
     * @return string
     */
    protected function _link(mixed $rawData): string
    {
        return '#';
    }

    /**
     * The email is only validated when Azure reports it as verified
     *
     * `upn` and `email` can be set by any tenant administrator, so their presence does not prove
     * the user owns the email. Only the `email_verified` and `xms_edov` (email domain owner verified)
     * claims are trusted.
     *
     * @param mixed $rawData raw data
     * @return bool
     */
    protected function _validated(mixed $rawData): bool
    {
        return ($rawData['email_verified'] ?? null) === true || ($rawData['xms_edov'] ?? null) === true;
    }
}
