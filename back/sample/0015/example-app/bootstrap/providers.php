<?php

/**
 * bootstrap/providers.php — Laravel 11+ provider registration.
 *
 * After running `php artisan ddd:make-context Order`, register the generated
 * context ServiceProvider here so Laravel boots it on every request.
 *
 * The package's auto-discover feature can handle this automatically when
 * 'auto_discover' => true in config/ddd-architect.php, but explicit
 * registration is recommended for production environments.
 */

return [
    App\Providers\AppServiceProvider::class,

    // -------------------------------------------------------------------------
    // DDD Bounded Context Providers
    // Each context's provider loads its routes, migrations, and bindings.
    // -------------------------------------------------------------------------
    App\Infrastructure\Providers\OrderInfrastructureServiceProvider::class,

    // Add additional bounded context providers here as your system grows:
    // App\Infrastructure\Providers\BillingInfrastructureServiceProvider::class,
    // App\Infrastructure\Providers\IdentityInfrastructureServiceProvider::class,
    // App\Infrastructure\Providers\InventoryInfrastructureServiceProvider::class,
];
