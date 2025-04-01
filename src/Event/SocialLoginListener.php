<?php
declare(strict_types=1);

namespace CakeDC\Auth\Event;

use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;
use CakeDC\Users\Plugin;

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
            Plugin::EVENT_SOCIAL_LOGIN_EXISTING_ACCOUNT => 'changeRole',
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