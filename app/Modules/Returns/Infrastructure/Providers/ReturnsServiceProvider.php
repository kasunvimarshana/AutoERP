<?php

namespace Modules\Returns\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Returns\Application\Contracts\ApplyCreditMemoServiceInterface;
use Modules\Returns\Application\Contracts\ApproveReturnAuthorizationServiceInterface;
use Modules\Returns\Application\Contracts\ApproveStockReturnServiceInterface;
use Modules\Returns\Application\Contracts\CancelReturnAuthorizationServiceInterface;
use Modules\Returns\Application\Contracts\CancelStockReturnServiceInterface;
use Modules\Returns\Application\Contracts\CompleteStockReturnServiceInterface;
use Modules\Returns\Application\Contracts\CreateCreditMemoServiceInterface;
use Modules\Returns\Application\Contracts\CreateReturnAuthorizationServiceInterface;
use Modules\Returns\Application\Contracts\CreateStockReturnServiceInterface;
use Modules\Returns\Application\Contracts\ExpireReturnAuthorizationServiceInterface;
use Modules\Returns\Application\Contracts\FailQualityCheckServiceInterface;
use Modules\Returns\Application\Contracts\IssueCreditMemoDirectServiceInterface;
use Modules\Returns\Application\Contracts\IssueCreditMemoServiceInterface;
use Modules\Returns\Application\Contracts\PassQualityCheckServiceInterface;
use Modules\Returns\Application\Contracts\ProcessReturnInventoryAdjustmentServiceInterface;
use Modules\Returns\Application\Contracts\VoidCreditMemoServiceInterface;
use Modules\Returns\Application\Services\ApplyCreditMemoService;
use Modules\Returns\Application\Services\ApproveReturnAuthorizationService;
use Modules\Returns\Application\Services\ApproveStockReturnService;
use Modules\Returns\Application\Services\CancelReturnAuthorizationService;
use Modules\Returns\Application\Services\CancelStockReturnService;
use Modules\Returns\Application\Services\CompleteStockReturnService;
use Modules\Returns\Application\Services\CreateCreditMemoService;
use Modules\Returns\Application\Services\CreateReturnAuthorizationService;
use Modules\Returns\Application\Services\CreateStockReturnService;
use Modules\Returns\Application\Services\ExpireReturnAuthorizationService;
use Modules\Returns\Application\Services\FailQualityCheckService;
use Modules\Returns\Application\Services\IssueCreditMemoDirectService;
use Modules\Returns\Application\Services\IssueCreditMemoService;
use Modules\Returns\Application\Services\PassQualityCheckService;
use Modules\Returns\Application\Services\ProcessReturnInventoryAdjustmentService;
use Modules\Returns\Application\Services\VoidCreditMemoService;
use Modules\Returns\Domain\RepositoryInterfaces\CreditMemoRepositoryInterface;
use Modules\Returns\Domain\RepositoryInterfaces\ReturnAuthorizationRepositoryInterface;
use Modules\Returns\Domain\RepositoryInterfaces\StockReturnLineRepositoryInterface;
use Modules\Returns\Domain\RepositoryInterfaces\StockReturnRepositoryInterface;
use Modules\Returns\Infrastructure\Persistence\Eloquent\Repositories\EloquentCreditMemoRepository;
use Modules\Returns\Infrastructure\Persistence\Eloquent\Repositories\EloquentReturnAuthorizationRepository;
use Modules\Returns\Infrastructure\Persistence\Eloquent\Repositories\EloquentStockReturnLineRepository;
use Modules\Returns\Infrastructure\Persistence\Eloquent\Repositories\EloquentStockReturnRepository;

class ReturnsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StockReturnRepositoryInterface::class, EloquentStockReturnRepository::class);
        $this->app->bind(StockReturnLineRepositoryInterface::class, EloquentStockReturnLineRepository::class);
        $this->app->bind(CreditMemoRepositoryInterface::class, EloquentCreditMemoRepository::class);
        $this->app->bind(ReturnAuthorizationRepositoryInterface::class, EloquentReturnAuthorizationRepository::class);

        $this->app->bind(CreateStockReturnServiceInterface::class, CreateStockReturnService::class);
        $this->app->bind(ApproveStockReturnServiceInterface::class, ApproveStockReturnService::class);
        $this->app->bind(CancelStockReturnServiceInterface::class, CancelStockReturnService::class);
        $this->app->bind(CompleteStockReturnServiceInterface::class, CompleteStockReturnService::class);
        $this->app->bind(ProcessReturnInventoryAdjustmentServiceInterface::class, ProcessReturnInventoryAdjustmentService::class);
        $this->app->bind(IssueCreditMemoServiceInterface::class, IssueCreditMemoService::class);
        $this->app->bind(PassQualityCheckServiceInterface::class, PassQualityCheckService::class);
        $this->app->bind(FailQualityCheckServiceInterface::class, FailQualityCheckService::class);
        $this->app->bind(CreateCreditMemoServiceInterface::class, CreateCreditMemoService::class);
        $this->app->bind(IssueCreditMemoDirectServiceInterface::class, IssueCreditMemoDirectService::class);
        $this->app->bind(ApplyCreditMemoServiceInterface::class, ApplyCreditMemoService::class);
        $this->app->bind(VoidCreditMemoServiceInterface::class, VoidCreditMemoService::class);
        $this->app->bind(CreateReturnAuthorizationServiceInterface::class, CreateReturnAuthorizationService::class);
        $this->app->bind(ApproveReturnAuthorizationServiceInterface::class, ApproveReturnAuthorizationService::class);
        $this->app->bind(CancelReturnAuthorizationServiceInterface::class, CancelReturnAuthorizationService::class);
        $this->app->bind(ExpireReturnAuthorizationServiceInterface::class, ExpireReturnAuthorizationService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
