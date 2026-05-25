<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Application\Configuration\CoreConfigKey;
use Modules\Core\Application\Contracts\ClockInterface;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Core\Application\Contracts\SlugGeneratorInterface;
use Modules\Core\Application\Contracts\UuidGeneratorInterface;
use Modules\Core\Infrastructure\Services\FileStorageService;
use Modules\Core\Infrastructure\Services\SlugGenerator;
use Modules\Core\Infrastructure\Support\LaravelUuidGenerator;
use Modules\Core\Infrastructure\Support\SystemClock;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../Infrastructure/Config/core.php', 'core');

        $this->app->singleton(ClockInterface::class, SystemClock::class);
        $this->app->singleton(UuidGeneratorInterface::class, LaravelUuidGenerator::class);
        $this->app->bind(FileStorageServiceInterface::class, FileStorageService::class);
        $this->app->bind(SlugGeneratorInterface::class, SlugGenerator::class);

        $this->app->when(FileStorageService::class)
            ->needs('$defaultDisk')
            ->give(static fn (): string => (string) Config::get(CoreConfigKey::FILE_STORAGE_DEFAULT_DISK->value));

        $this->app->when(SlugGenerator::class)
            ->needs('$fallback')
            ->give(static fn (): string => (string) Config::get(CoreConfigKey::SLUG_FALLBACK->value));
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../Infrastructure/Config/core.php' => config_path('core.php'),
        ], 'core-config');
    }
}
