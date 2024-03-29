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

namespace CakeDC\Auth\Social;

use CakeDC\Auth\Social\Service\ServiceInterface;
use InvalidArgumentException;

class MapUser
{
    /**
     * Map social user user data
     *
     * @param \CakeDC\Auth\Social\Service\ServiceInterface $service social service
     * @param mixed $data user social data
     * @return mixed
     */
    public function __invoke(ServiceInterface $service, mixed $data): mixed
    {
        $mapper = $service->getConfig('mapper');
        if (is_string($mapper)) {
            $mapper = $this->buildMapper($mapper);
        }

        $user = $mapper($data);
        $user['provider'] = $service->getProviderName();

        return $user;
    }

    /**
     * Build the mapper object
     *
     * @param string $className of mapper
     * @return object
     */
    protected function buildMapper(string $className): object
    {
        if (!class_exists($className)) {
            throw new InvalidArgumentException(__('Provider mapper class {0} does not exist', $className));
        }

        return new $className();
    }
}
