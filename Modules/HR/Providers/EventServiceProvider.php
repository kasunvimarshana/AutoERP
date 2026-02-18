<?php

declare(strict_types=1);

namespace Modules\HR\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\HR\Events\EmployeeHired;
use Modules\HR\Events\LeaveApproved;
use Modules\HR\Events\PayrollProcessed;
use Modules\HR\Events\PerformanceReviewCompleted;
use Modules\HR\Listeners\CreatePayrollJournalListener;

/**
 * HR Event Service Provider
 *
 * Registers event listeners for the HR module.
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        PayrollProcessed::class => [
            CreatePayrollJournalListener::class,
        ],
        EmployeeHired::class => [
            // Add listeners here when needed
        ],
        LeaveApproved::class => [
            // Add listeners here when needed
        ],
        PerformanceReviewCompleted::class => [
            // Add listeners here when needed
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
