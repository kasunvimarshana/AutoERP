<?php

namespace App\Providers;

use App\Domain\Events\TenantCreated;
use App\Domain\Events\UserLoggedIn;
use App\Domain\Events\UserRegistered;
use App\Listeners\LogAuditEvent;
use App\Listeners\NotifyTenantCreated;
use App\Listeners\SendWelcomeEmail;
use App\Listeners\UpdateLoginMetrics;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Event-listener mappings.
     */
    protected $listen = [
        UserLoggedIn::class => [
            UpdateLoginMetrics::class,
            LogAuditEvent::class,
        ],
        UserRegistered::class => [
            SendWelcomeEmail::class,
            LogAuditEvent::class,
        ],
        TenantCreated::class => [
            NotifyTenantCreated::class,
            LogAuditEvent::class,
        ],
        PasswordReset::class => [
            LogAuditEvent::class,
        ],
    ];

    /**
     * Determine if events and listeners should be auto-discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
