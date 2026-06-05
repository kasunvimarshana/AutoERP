<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

final class HrServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/hr.php', 'hr');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../Infrastructure/Persistence/Eloquent/Migrations');
    }
}
