<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\GatewayProxyInterface;
use App\Services\GatewayProxy;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(GatewayProxyInterface::class, GatewayProxy::class);
    }

    public function boot(): void {}
}
