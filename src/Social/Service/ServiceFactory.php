<?php
/**
 * Copyright 2010 - 2019, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010 - 2018, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Auth\Social\Service;

use CakeDC\Auth\Social\ProviderConfig;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\ServerRequest;

class ServiceFactory
{

    protected $redirectUriField = 'redirectUri';

    /**
     * @param string $redirectUriField field used for redirect uri
     *
     * @return self
     */
    public function setRedirectUriField($redirectUriField)
    {
        $this->redirectUriField = $redirectUriField;

        return $this;
    }

    /**
     * Create a new service based on provider alias
     *
     * @param string $provider provider alias
     *
     * @return ServiceInterface
     */
    public function createFromProvider($provider)
    {
        $config = (new ProviderConfig())->getConfig($provider);

        if (!$provider || !$config) {
            throw new NotFoundException('Provider not found');
        }

        $config['options']['redirectUri'] = $config['options'][$this->redirectUriField];
        unset($config['options']['linkSocialUri'], $config['options']['callbackLinkSocialUri']);
        $service = new $config['service']($config);
        $service->setProviderName($provider);

        return $service;
    }

    /**
     * Create a new service based on request
     *
     * @param ServerRequest $request in use
     *
     * @return ServiceInterface
     */
    public function createFromRequest(ServerRequest $request)
    {
        $params = $request->getAttribute('params');
        $provider = isset($params['provider']) ? $params['provider'] : null;

        return $this->createFromProvider($provider);
    }
}
