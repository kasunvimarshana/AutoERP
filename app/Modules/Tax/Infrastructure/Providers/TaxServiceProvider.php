<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\Tax\Application\Contracts\CreateTaxGroupServiceInterface;
use Modules\Tax\Application\Contracts\CreateTaxRateServiceInterface;
use Modules\Tax\Application\Contracts\CreateTaxRuleServiceInterface;
use Modules\Tax\Application\Contracts\DeleteTaxGroupServiceInterface;
use Modules\Tax\Application\Contracts\DeleteTaxRateServiceInterface;
use Modules\Tax\Application\Contracts\DeleteTaxRuleServiceInterface;
use Modules\Tax\Application\Contracts\FindTaxGroupServiceInterface;
use Modules\Tax\Application\Contracts\FindTaxRateServiceInterface;
use Modules\Tax\Application\Contracts\FindTaxRuleServiceInterface;
use Modules\Tax\Application\Contracts\FindTransactionTaxServiceInterface;
use Modules\Tax\Application\Contracts\RecordTransactionTaxesServiceInterface;
use Modules\Tax\Application\Contracts\ResolveTaxServiceInterface;
use Modules\Tax\Application\Contracts\UpdateTaxGroupServiceInterface;
use Modules\Tax\Application\Contracts\UpdateTaxRateServiceInterface;
use Modules\Tax\Application\Contracts\UpdateTaxRuleServiceInterface;
use Modules\Tax\Application\Services\CreateTaxGroupService;
use Modules\Tax\Application\Services\CreateTaxRateService;
use Modules\Tax\Application\Services\CreateTaxRuleService;
use Modules\Tax\Application\Services\DeleteTaxGroupService;
use Modules\Tax\Application\Services\DeleteTaxRateService;
use Modules\Tax\Application\Services\DeleteTaxRuleService;
use Modules\Tax\Application\Services\FindTaxGroupService;
use Modules\Tax\Application\Services\FindTaxRateService;
use Modules\Tax\Application\Services\FindTaxRuleService;
use Modules\Tax\Application\Services\FindTransactionTaxService;
use Modules\Tax\Application\Services\RecordTransactionTaxesService;
use Modules\Tax\Application\Services\ResolveTaxService;
use Modules\Tax\Application\Services\UpdateTaxGroupService;
use Modules\Tax\Application\Services\UpdateTaxRateService;
use Modules\Tax\Application\Services\UpdateTaxRuleService;
use Modules\Tax\Domain\RepositoryInterfaces\TaxGroupRepositoryInterface;
use Modules\Tax\Domain\RepositoryInterfaces\TaxRateRepositoryInterface;
use Modules\Tax\Domain\RepositoryInterfaces\TaxRuleRepositoryInterface;
use Modules\Tax\Domain\RepositoryInterfaces\TransactionTaxRepositoryInterface;
use Modules\Tax\Infrastructure\Persistence\Eloquent\Repositories\EloquentTaxGroupRepository;
use Modules\Tax\Infrastructure\Persistence\Eloquent\Repositories\EloquentTaxRateRepository;
use Modules\Tax\Infrastructure\Persistence\Eloquent\Repositories\EloquentTaxRuleRepository;
use Modules\Tax\Infrastructure\Persistence\Eloquent\Repositories\EloquentTransactionTaxRepository;

class TaxServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $this->app->bind(TaxGroupRepositoryInterface::class, EloquentTaxGroupRepository::class);
        $this->app->bind(TaxRateRepositoryInterface::class, EloquentTaxRateRepository::class);
        $this->app->bind(TaxRuleRepositoryInterface::class, EloquentTaxRuleRepository::class);
        $this->app->bind(TransactionTaxRepositoryInterface::class, EloquentTransactionTaxRepository::class);

        $this->app->bind(CreateTaxGroupServiceInterface::class, CreateTaxGroupService::class);
        $this->app->bind(FindTaxGroupServiceInterface::class, FindTaxGroupService::class);
        $this->app->bind(UpdateTaxGroupServiceInterface::class, UpdateTaxGroupService::class);
        $this->app->bind(DeleteTaxGroupServiceInterface::class, DeleteTaxGroupService::class);

        $this->app->bind(CreateTaxRateServiceInterface::class, CreateTaxRateService::class);
        $this->app->bind(FindTaxRateServiceInterface::class, FindTaxRateService::class);
        $this->app->bind(UpdateTaxRateServiceInterface::class, UpdateTaxRateService::class);
        $this->app->bind(DeleteTaxRateServiceInterface::class, DeleteTaxRateService::class);

        $this->app->bind(CreateTaxRuleServiceInterface::class, CreateTaxRuleService::class);
        $this->app->bind(FindTaxRuleServiceInterface::class, FindTaxRuleService::class);
        $this->app->bind(UpdateTaxRuleServiceInterface::class, UpdateTaxRuleService::class);
        $this->app->bind(DeleteTaxRuleServiceInterface::class, DeleteTaxRuleService::class);

        $this->app->bind(ResolveTaxServiceInterface::class, ResolveTaxService::class);
        $this->app->bind(RecordTransactionTaxesServiceInterface::class, RecordTransactionTaxesService::class);
        $this->app->bind(FindTransactionTaxServiceInterface::class, FindTransactionTaxService::class);
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__.'/../../routes/api.php',
            __DIR__.'/../../database/migrations',
        );
    }
}
