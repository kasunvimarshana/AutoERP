<?php

namespace Modules\Media\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Media\Domain\Contracts\MediaRepositoryInterface;
use Modules\Media\Infrastructure\Repositories\MediaRepository;

class MediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'media');
        $this->app->bind(MediaRepositoryInterface::class, MediaRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
    }
}
