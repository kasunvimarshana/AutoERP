<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Contracts\NotificationServiceInterface;
use App\Infrastructure\Services\NotificationService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(NotificationServiceInterface::class, NotificationService::class);
    }

    public function boot(): void {}
}
