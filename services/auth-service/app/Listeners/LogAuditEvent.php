<?php

namespace App\Listeners;

use App\Domain\Events\UserLoggedIn;
use App\Domain\Events\UserRegistered;
use App\Domain\Events\TenantCreated;
use App\Domain\Models\AuditLog;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Events\Dispatcher;

class LogAuditEvent
{
    public function handle(mixed $event): void
    {
        if (!config('app.features.audit_log', true)) {
            return;
        }

        match (true) {
            $event instanceof UserLoggedIn     => $this->logUserLogin($event),
            $event instanceof UserRegistered   => $this->logUserRegistered($event),
            $event instanceof TenantCreated    => $this->logTenantCreated($event),
            $event instanceof PasswordReset    => $this->logPasswordReset($event),
            default => null,
        };
    }

    private function logUserLogin(UserLoggedIn $event): void
    {
        AuditLog::record(
            event: 'user.login',
            userId: $event->user->id,
            tenantId: $event->tenantId ?? $event->user->tenant_id,
            auditable: $event->user,
            metadata: ['ip' => $event->ipAddress, 'device_id' => $event->deviceId],
        );
    }

    private function logUserRegistered(UserRegistered $event): void
    {
        AuditLog::record(
            event: 'user.registered',
            userId: $event->user->id,
            tenantId: $event->tenantId,
            auditable: $event->user,
            metadata: ['ip' => $event->ipAddress],
        );
    }

    private function logTenantCreated(TenantCreated $event): void
    {
        AuditLog::record(
            event: 'tenant.created',
            userId: $event->adminUserId,
            tenantId: $event->tenant->id,
            auditable: $event->tenant,
            newValues: [
                'name'      => $event->tenant->name,
                'subdomain' => $event->tenant->subdomain,
                'plan'      => $event->tenant->plan,
            ],
        );
    }

    private function logPasswordReset(PasswordReset $event): void
    {
        AuditLog::record(
            event: 'user.password_reset',
            userId: $event->user->id,
            tenantId: $event->user->tenant_id,
            auditable: $event->user,
            metadata: ['ip' => request()->ip()],
        );
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(UserLoggedIn::class, [self::class, 'handle']);
        $events->listen(UserRegistered::class, [self::class, 'handle']);
        $events->listen(TenantCreated::class, [self::class, 'handle']);
        $events->listen(PasswordReset::class, [self::class, 'handle']);
    }
}
