<?php

declare(strict_types=1);

namespace Modules\Customer\Providers;

use Illuminate\Support\ServiceProvider;

final class CustomerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
