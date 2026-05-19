<?php

namespace Modules\OrganizationUnit\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

class OrganizationUnitServiceProvider extends ServiceProvider
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
