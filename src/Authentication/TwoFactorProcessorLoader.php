<?php
declare(strict_types=1);

/**
 * Copyright 2010 - 2024, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010 - 2024, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace CakeDC\Auth\Authentication;

use Cake\Core\Configure;

/**
 * TwoFactorProcessor loader
 */
class TwoFactorProcessorLoader
{
    /**
     * Loads processors collection
     *
     * @return \CakeDC\Auth\Authentication\TwoFactorProcessorCollection
     */
    public static function processors(): TwoFactorProcessorCollection
    {
        $processors = Configure::read('TwoFactorProcessors') ?? [];
        $collection = new TwoFactorProcessorCollection();
        $collection->addMany($processors);

        return $collection;
    }
}
