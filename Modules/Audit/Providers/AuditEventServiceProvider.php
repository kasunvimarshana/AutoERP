<?php

declare(strict_types=1);

namespace Modules\Audit\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Audit\Listeners\LogPriceCreated;
use Modules\Audit\Listeners\LogPriceUpdated;
use Modules\Audit\Listeners\LogProductCreated;
use Modules\Audit\Listeners\LogProductUpdated;
use Modules\Audit\Listeners\LogUserCreated;
use Modules\Audit\Listeners\LogUserUpdated;
use Modules\Auth\Events\UserCreated;
use Modules\Auth\Events\UserUpdated;
use Modules\Pricing\Events\PriceCreated;
use Modules\Pricing\Events\PriceUpdated;
use Modules\Product\Events\ProductCreated;
use Modules\Product\Events\ProductUpdated;

/**
 * AuditEventServiceProvider
 *
 * Registers event listeners for audit logging
 */
class AuditEventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        ProductCreated::class => [
            LogProductCreated::class,
        ],
        ProductUpdated::class => [
            LogProductUpdated::class,
        ],
        UserCreated::class => [
            LogUserCreated::class,
        ],
        UserUpdated::class => [
            LogUserUpdated::class,
        ],
        PriceCreated::class => [
            LogPriceCreated::class,
        ],
        PriceUpdated::class => [
            LogPriceUpdated::class,
        ],
    ];

    /**
     * Register services
     */
    public function register(): void
    {
        parent::register();
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        parent::boot();
    }
}
