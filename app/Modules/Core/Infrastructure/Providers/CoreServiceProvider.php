<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Providers;

use Illuminate\Contracts\Broadcasting\Factory as BroadcastingFactory;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use L5Swagger\Generator;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Core\Infrastructure\ApiDoc\Contracts\ApiDocServiceInterface;
use Modules\Core\Infrastructure\ApiDoc\Services\SwaggerApiDocService;
use Modules\Core\Infrastructure\Broadcasting\Contracts\BroadcastServiceInterface;
use Modules\Core\Infrastructure\Broadcasting\Contracts\ChannelManagerInterface;
use Modules\Core\Infrastructure\Broadcasting\Contracts\EventBroadcasterInterface;
use Modules\Core\Infrastructure\Broadcasting\Services\BroadcastService;
use Modules\Core\Infrastructure\Broadcasting\Services\ChannelManager;
use Modules\Core\Infrastructure\Broadcasting\Services\EventBroadcaster;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Core\Infrastructure\Services\FileStorageService;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RepositoryInterface::class, EloquentRepository::class);
        $this->app->bind(FileStorageServiceInterface::class, function ($app) {
            $defaultDisk = config('core.file_storage.default_disk', 'public');

            return new FileStorageService($defaultDisk);
        });

        $this->app->bind(ApiDocServiceInterface::class, function ($app) {
            return new SwaggerApiDocService($app->make(Generator::class));
        });

        // Broadcasting — registered as singletons so all modules share the same
        // ChannelManager instance and channel definitions are not duplicated.
        $this->app->singleton(ChannelManagerInterface::class, ChannelManager::class);

        $this->app->singleton(BroadcastServiceInterface::class, function ($app) {
            return new BroadcastService($app->make(BroadcastingFactory::class));
        });

        $this->app->bind(EventBroadcasterInterface::class, function ($app) {
            return new EventBroadcaster($app->make(Dispatcher::class));
        });

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

        // Register all channel authorization callbacks collected by ChannelManager
        // implementations across every module's service-provider boot phase.
        $this->app->make(ChannelManagerInterface::class)->registerAll();
    }
}
