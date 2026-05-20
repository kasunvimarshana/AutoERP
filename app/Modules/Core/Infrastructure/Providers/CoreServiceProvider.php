<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Core\Application\Contracts\SlugGeneratorInterface;
use Modules\Core\Infrastructure\Services\FileStorageService;
use Modules\Core\Infrastructure\Services\SlugGenerator;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../Infrastructure/Config/core.php', 'core');

        $this->app->bind(FileStorageServiceInterface::class, FileStorageService::class);
        $this->app->bind(SlugGeneratorInterface::class, SlugGenerator::class);
        $this->app->when(FileStorageService::class)
            ->needs('$defaultDisk')
            ->give(static fn (): string => (string) config('core.file_storage.default_disk', 'public'));
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../Infrastructure/Config/core.php' => config_path('core.php'),
        ], 'core-config');
    }
}
