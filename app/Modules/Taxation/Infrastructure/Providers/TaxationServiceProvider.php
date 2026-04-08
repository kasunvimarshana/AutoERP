<?php

declare(strict_types=1);

namespace Modules\Taxation\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Taxation\Application\Contracts\ActivateTaxRateServiceInterface;
use Modules\Taxation\Application\Contracts\CreateTaxRateServiceInterface;
use Modules\Taxation\Application\Contracts\CreateTaxRuleServiceInterface;
use Modules\Taxation\Application\Contracts\DeactivateTaxRateServiceInterface;
use Modules\Taxation\Application\Contracts\DeleteTaxRateServiceInterface;
use Modules\Taxation\Application\Contracts\DeleteTaxRuleServiceInterface;
use Modules\Taxation\Application\Contracts\FindTaxRateServiceInterface;
use Modules\Taxation\Application\Contracts\FindTaxRuleServiceInterface;
use Modules\Taxation\Application\Contracts\UpdateTaxRateServiceInterface;
use Modules\Taxation\Application\Contracts\UpdateTaxRuleServiceInterface;
use Modules\Taxation\Application\Services\ActivateTaxRateService;
use Modules\Taxation\Application\Services\CreateTaxRateService;
use Modules\Taxation\Application\Services\CreateTaxRuleService;
use Modules\Taxation\Application\Services\DeactivateTaxRateService;
use Modules\Taxation\Application\Services\DeleteTaxRateService;
use Modules\Taxation\Application\Services\DeleteTaxRuleService;
use Modules\Taxation\Application\Services\FindTaxRateService;
use Modules\Taxation\Application\Services\FindTaxRuleService;
use Modules\Taxation\Application\Services\UpdateTaxRateService;
use Modules\Taxation\Application\Services\UpdateTaxRuleService;
use Modules\Taxation\Domain\RepositoryInterfaces\TaxRateRepositoryInterface;
use Modules\Taxation\Domain\RepositoryInterfaces\TaxRuleRepositoryInterface;
use Modules\Taxation\Infrastructure\Persistence\Eloquent\Models\TaxRateModel;
use Modules\Taxation\Infrastructure\Persistence\Eloquent\Models\TaxRuleModel;
use Modules\Taxation\Infrastructure\Persistence\Eloquent\Repositories\EloquentTaxRateRepository;
use Modules\Taxation\Infrastructure\Persistence\Eloquent\Repositories\EloquentTaxRuleRepository;

class TaxationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TaxRateRepositoryInterface::class, fn ($app) =>
            new EloquentTaxRateRepository($app->make(TaxRateModel::class)));

        $this->app->bind(TaxRuleRepositoryInterface::class, fn ($app) =>
            new EloquentTaxRuleRepository($app->make(TaxRuleModel::class)));

        $this->app->bind(CreateTaxRateServiceInterface::class, fn ($app) =>
            new CreateTaxRateService($app->make(TaxRateRepositoryInterface::class)));

        $this->app->bind(FindTaxRateServiceInterface::class, fn ($app) =>
            new FindTaxRateService($app->make(TaxRateRepositoryInterface::class)));

        $this->app->bind(UpdateTaxRateServiceInterface::class, fn ($app) =>
            new UpdateTaxRateService($app->make(TaxRateRepositoryInterface::class)));

        $this->app->bind(DeleteTaxRateServiceInterface::class, fn ($app) =>
            new DeleteTaxRateService($app->make(TaxRateRepositoryInterface::class)));

        $this->app->bind(ActivateTaxRateServiceInterface::class, fn ($app) =>
            new ActivateTaxRateService($app->make(TaxRateRepositoryInterface::class)));

        $this->app->bind(DeactivateTaxRateServiceInterface::class, fn ($app) =>
            new DeactivateTaxRateService($app->make(TaxRateRepositoryInterface::class)));

        $this->app->bind(CreateTaxRuleServiceInterface::class, fn ($app) =>
            new CreateTaxRuleService($app->make(TaxRuleRepositoryInterface::class)));

        $this->app->bind(FindTaxRuleServiceInterface::class, fn ($app) =>
            new FindTaxRuleService($app->make(TaxRuleRepositoryInterface::class)));

        $this->app->bind(UpdateTaxRuleServiceInterface::class, fn ($app) =>
            new UpdateTaxRuleService($app->make(TaxRuleRepositoryInterface::class)));

        $this->app->bind(DeleteTaxRuleServiceInterface::class, fn ($app) =>
            new DeleteTaxRuleService($app->make(TaxRuleRepositoryInterface::class)));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        Route::middleware(['api', 'auth:api', 'resolve.tenant'])
            ->prefix('api')
            ->group(function () {
                $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
            });
    }
}
