<?php
declare(strict_types=1);

namespace CakeDC\Auth\Panel;

use Cake\Event\EventInterface;
use CakeDC\Auth\Policy\Result\RbacResult;
use CakeDC\Auth\Policy\Result\SuperuserResult;
use DebugKit\DebugPanel;

class PermissionsPanel extends DebugPanel
{
    /**
     * @inheritDoc
     */
    public string $plugin = 'CakeDC/Auth';

    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        $this->_data = ['results' => []];
    }

    /**
     * Get the events this panels supports.
     *
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        return [
            'Controller.shutdown' => 'shutdown',
            'CakeDC/Auth.DebugKit.Permission.afterResult' => 'afterResult',
        ];
    }

    /**
     * @param \Cake\Event\EventInterface $event
     * @return void
     */
    public function afterResult(EventInterface $event): void
    {
        $subject = $event->getSubject();
        if ($subject instanceof RbacResult) {
            $this->parseRbacResult($subject);

            return;
        }
        if ($subject instanceof SuperuserResult) {
            $this->parseSuperuserResult($subject);
        }
    }

    /**
     * @param \CakeDC\Auth\Policy\Result\RbacResult $subject
     * @return void
     */
    protected function parseRbacResult(RbacResult $subject): void
    {
        $this->_data['results'][] = [
            'status' => $subject->getStatus(),
            'reason' => $subject->getReason(),
            'logReason' => $subject->getPermissionMatchResult()->getLogReason(),
            'resource' => $subject->getPermissionMatchResult()->getResource(),
            'permission' => $subject->getPermissionMatchResult()->getPermission(),
            'type' => 'Rbac',
        ];
    }

    /**
     * @param \CakeDC\Auth\Policy\Result\SuperuserResult $subject
     * @return void
     */
    protected function parseSuperuserResult(SuperuserResult $subject): void
    {
        $this->_data['results'][] = [
            'status' => $subject->getStatus(),
            'reason' => $subject->getReason(),
            'resource' => $subject->getResource(),
            'permission' => null,
            'type' => 'Superuser',
        ];
    }
}
