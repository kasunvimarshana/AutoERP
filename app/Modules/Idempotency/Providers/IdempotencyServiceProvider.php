<?php

declare(strict_types=1);

namespace Modules\Idempotency\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Idempotency\Services\IdempotencyService;

final class IdempotencyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(IdempotencyService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
