<?php

declare(strict_types=1);

namespace Modules\Audit\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Audit\Services\AuditService;
use Modules\Auth\Events\UserUpdated;

class LogUserUpdated implements ShouldQueue
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    public function handle(UserUpdated $event): void
    {
        $userData = $event->user->toArray();
        unset($userData['password']);

        $oldValues = $event->oldValues;
        unset($oldValues['password']);

        $this->auditService->log([
            'event' => 'user.updated',
            'auditable_type' => get_class($event->user),
            'auditable_id' => $event->user->id,
            'old_values' => $oldValues,
            'new_values' => $userData,
            'metadata' => [
                'username' => $event->user->username,
                'email' => $event->user->email,
            ],
        ]);
    }
}
