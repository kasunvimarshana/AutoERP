<?php

namespace Modules\SystemUser\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

class SystemUserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../Infrastructure/Persistence/Eloquent/Migrations');
    }
}
