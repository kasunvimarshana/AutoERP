<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Core\Infrastructure\Services\FileStorageService;

class CoreServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(RepositoryInterface::class, EloquentRepository::class);
        $this->app->bind(FileStorageServiceInterface::class, function ($app) {
            $defaultDisk = config('core.file_storage.default_disk', 'public');

            return new FileStorageService($defaultDisk);
        });

        $this->mergeConfigFrom(__DIR__.'/../../config/core.php', 'core');

        if (file_exists($helperFile = __DIR__.'/../../Shared/Helpers/helpers.php')) {
            require_once $helperFile;
        }
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/core.php' => config_path('core.php'),
        ], 'core-config');

        $this->publishes([
            __DIR__.'/../../database/migrations' => database_path('migrations'),
        ], 'core-migrations');
    }
}
