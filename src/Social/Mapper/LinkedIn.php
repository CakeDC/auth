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
 * LinkedIn Mapper
 *
 * @deprecated 11.1.0 LinkedIn retired the legacy Sign In with LinkedIn API, use the `linkedInOpenIDConnect` provider with the `LinkedInOpenIDConnect` mapper instead.
 */
class LinkedIn extends AbstractMapper
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
            'The LinkedIn mapper is deprecated, LinkedIn retired the legacy Sign In API. Use ' .
            'the `linkedInOpenIDConnect` provider with ' .
            '`CakeDC\\Auth\\Social\\Mapper\\LinkedInOpenIDConnect` instead.',
        );
        parent::__construct($mapFields);
    }

    /**
     * Map for provider fields
     *
     * @var array
     */
    protected array $_mapFields = [
        'avatar' => 'pictureUrl',
        'first_name' => 'firstName',
        'last_name' => 'lastName',
        'email' => 'emailAddress',
        'bio' => 'headline',
        'link' => 'publicProfileUrl',
    ];
}
