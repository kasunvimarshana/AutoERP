<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\OrderServiceInterface;
use App\Services\OrderService;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OrderServiceInterface::class, OrderService::class);
    }

    public function boot(): void {}
}
