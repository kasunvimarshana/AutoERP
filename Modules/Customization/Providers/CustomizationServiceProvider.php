<?php

declare(strict_types=1);

namespace Modules\Customization\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Customization\Application\Services\CustomFieldService;
use Modules\Customization\Application\Services\CustomFieldValueService;
use Modules\Customization\Domain\Contracts\CustomFieldRepositoryInterface;
use Modules\Customization\Domain\Contracts\CustomFieldValueRepositoryInterface;
use Modules\Customization\Infrastructure\Repositories\CustomFieldRepository;
use Modules\Customization\Infrastructure\Repositories\CustomFieldValueRepository;

class CustomizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            CustomFieldRepositoryInterface::class,
            CustomFieldRepository::class
        );

        $this->app->bind(
            CustomFieldValueRepositoryInterface::class,
            CustomFieldValueRepository::class
        );

        $this->app->singleton(CustomFieldService::class);
        $this->app->singleton(CustomFieldValueService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Infrastructure/Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Interfaces/Http/routes.php');
    }
}
