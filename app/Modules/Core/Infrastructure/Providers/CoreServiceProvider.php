<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Core\Application\Contracts\SlugGeneratorInterface;
use Modules\Core\Infrastructure\ApiDoc\Contracts\ApiDocServiceInterface;
use Modules\Core\Infrastructure\ApiDoc\Services\SwaggerApiDocService;
use Modules\Core\Infrastructure\Broadcasting\Contracts\BroadcastServiceInterface;
use Modules\Core\Infrastructure\Broadcasting\Contracts\ChannelManagerInterface;
use Modules\Core\Infrastructure\Broadcasting\Contracts\EventBroadcasterInterface;
use Modules\Core\Infrastructure\Broadcasting\Services\BroadcastService;
use Modules\Core\Infrastructure\Broadcasting\Services\ChannelManager;
use Modules\Core\Infrastructure\Broadcasting\Services\EventBroadcaster;
use Modules\Core\Infrastructure\Services\FileStorageService;
use Modules\Core\Infrastructure\Services\SlugGenerator;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(FileStorageServiceInterface::class, FileStorageService::class);
        $this->app->bind(SlugGeneratorInterface::class, SlugGenerator::class);
        $this->app->when(FileStorageService::class)
            ->needs('$defaultDisk')
            ->give(static fn (): string => (string) config('core.file_storage.default_disk', 'public'));

        $this->app->bind(ApiDocServiceInterface::class, SwaggerApiDocService::class);

        // Broadcasting — registered as singletons so all modules share the same
        // ChannelManager instance and channel definitions are not duplicated.
        $this->app->singleton(ChannelManagerInterface::class, ChannelManager::class);

        $this->app->singleton(BroadcastServiceInterface::class, BroadcastService::class);

        $this->app->bind(EventBroadcasterInterface::class, EventBroadcaster::class);

        $this->mergeConfigFrom(__DIR__.'/../../config/core.php', 'core');

        if (file_exists($helperFile = __DIR__.'/../../Shared/Helpers/helpers.php')) {
            require_once $helperFile;
        }
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/core.php' => config_path('core.php'),
        ], 'core-config');

        $this->publishes([
            __DIR__.'/../../database/migrations' => database_path('migrations'),
        ], 'core-migrations');

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        // Register all channel authorization callbacks collected by ChannelManager
        // implementations across every module's service-provider boot phase.
        $this->app->make(ChannelManagerInterface::class)->registerAll();
    }
}
