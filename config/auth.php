<?php
use Cake\Routing\Router;
return [
    'OAuth.providers' => [
        'facebook' => [
            'service' => 'CakeDC\Auth\Social\Service\OAuth2Service',
            'className' => 'League\OAuth2\Client\Provider\Facebook',
            'mapper' => 'CakeDC\Auth\Social\Mapper\Facebook',
            'options' => [
                'graphApiVersion' => 'v2.8', //bio field was deprecated on >= v2.8
                'redirectUri' => Router::fullBaseUrl() . '/auth/facebook',
                'linkSocialUri' => Router::fullBaseUrl() . '/link-social/facebook',
                'callbackLinkSocialUri' => Router::fullBaseUrl() . '/callback-link-social/facebook',
            ]
        ],
        'twitter' => [
            'service' => 'CakeDC\Auth\Social\Service\OAuth1Service',
            'className' => 'League\OAuth1\Client\Server\Twitter',
            'mapper' => 'CakeDC\Auth\Social\Mapper\Twitter',
            'options' => [
                'redirectUri' => Router::fullBaseUrl() . '/auth/twitter',
                'linkSocialUri' => Router::fullBaseUrl() . '/link-social/twitter',
                'callbackLinkSocialUri' => Router::fullBaseUrl() . '/callback-link-social/twitter',
            ]
        ],
        'linkedIn' => [
            'service' => 'CakeDC\Auth\Social\Service\OAuth2Service',
            'className' => 'League\OAuth2\Client\Provider\LinkedIn',
            'mapper' => 'CakeDC\Auth\Social\Mapper\LinkedIn',
            'options' => [
                'redirectUri' => Router::fullBaseUrl() . '/auth/linkedIn',
                'linkSocialUri' => Router::fullBaseUrl() . '/link-social/linkedIn',
                'callbackLinkSocialUri' => Router::fullBaseUrl() . '/callback-link-social/linkedIn',
            ]
        ],
        'instagram' => [
            'service' => 'CakeDC\Auth\Social\Service\OAuth2Service',
            'className' => 'League\OAuth2\Client\Provider\Instagram',
            'mapper' => 'CakeDC\Auth\Social\Mapper\Instagram',
            'options' => [
                'redirectUri' => Router::fullBaseUrl() . '/auth/instagram',
                'linkSocialUri' => Router::fullBaseUrl() . '/link-social/instagram',
                'callbackLinkSocialUri' => Router::fullBaseUrl() . '/callback-link-social/instagram',
            ]
        ],
        'google' => [
            'service' => 'CakeDC\Auth\Social\Service\OAuth2Service',
            'className' => 'League\OAuth2\Client\Provider\Google',
            'mapper' => 'CakeDC\Auth\Social\Mapper\Google',
            'options' => [
                'userFields' => ['url', 'aboutMe'],
                'redirectUri' => Router::fullBaseUrl() . '/auth/google',
                'linkSocialUri' => Router::fullBaseUrl() . '/link-social/google',
                'callbackLinkSocialUri' => Router::fullBaseUrl() . '/callback-link-social/google',
            ]
        ],
        'amazon' => [
            'service' => 'CakeDC\Auth\Social\Service\OAuth2Service',
            'className' => 'Luchianenco\OAuth2\Client\Provider\Amazon',
            'mapper' => 'CakeDC\Auth\Social\Mapper\Amazon',
            'options' => [
                'redirectUri' => Router::fullBaseUrl() . '/auth/amazon',
                'linkSocialUri' => Router::fullBaseUrl() . '/link-social/amazon',
                'callbackLinkSocialUri' => Router::fullBaseUrl() . '/callback-link-social/amazon',
            ]
        ],
    ],
    'OneTimePasswordAuthenticator' => [
    'checker' => \CakeDC\Auth\Authentication\DefaultTwoFactorAuthenticationChecker::class,
    'verifyAction' => [
        'plugin' => 'CakeDC/Users',
        'controller' => 'Users',
        'action' => 'verify',
        'prefix' => false,
    ],
],
];
