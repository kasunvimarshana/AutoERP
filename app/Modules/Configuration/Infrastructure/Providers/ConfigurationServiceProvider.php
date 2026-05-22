<?php

namespace Modules\Configuration\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Configuration\Domain\Contracts\ConfigurationReadRepositoryContract;
use Modules\Configuration\Domain\Contracts\ConfigurationWriteRepositoryContract;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Repositories\EloquentConfigurationReadRepository;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Repositories\EloquentConfigurationWriteRepository;

class ConfigurationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ConfigurationReadRepositoryContract::class, EloquentConfigurationReadRepository::class);
        $this->app->bind(ConfigurationWriteRepositoryContract::class, EloquentConfigurationWriteRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../Infrastructure/Persistence/Eloquent/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../Presentation/Routes/api.php');
    }
}
