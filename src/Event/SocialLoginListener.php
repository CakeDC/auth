<?php
declare(strict_types=1);

namespace CakeDC\Auth\Event;

use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;

class SocialLoginListener implements EventListenerInterface
{
    /**
     * Implementálja az EventListenerInterface-t
     *
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        return [
            /**
             * Event name directly used from CakeDC/Users plugin
             * Original source: CakeDC\Users\Plugin::EVENT_SOCIAL_LOGIN_EXISTING_ACCOUNT
             * If the constant is changed in the CakeDC/Users plugin, this string must be updated accordingly
             */
            'CakeDC.Users.Social.afterIdentify' => 'changeRole',
        ];
    }

    /**
     * Szerepkör módosítása Keycloak bejelentkezés esetén
     *
     * @param \Cake\Event\EventInterface $event Az esemény objektum
     * @return \Cake\Datasource\EntityInterface|null
     */
    public function changeRole(EventInterface $event)
    {
        $data = $event->getData('data');
        $userEntity = $event->getData('userEntity');
        if (isset($data['provider']) && $data['provider'] === 'keycloak' && isset($data['roles'])) {
            $userEntity->set('role', $data['roles']);
            return $userEntity;
        }

        return null;
    }
} 