<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Returns\Application\Contracts\ApproveStockReturnServiceInterface;
use Modules\Returns\Application\Contracts\CancelStockReturnServiceInterface;
use Modules\Returns\Application\Contracts\CompleteStockReturnServiceInterface;
use Modules\Returns\Application\Contracts\CreateStockReturnLineServiceInterface;
use Modules\Returns\Application\Contracts\CreateStockReturnServiceInterface;
use Modules\Returns\Application\Contracts\DeleteStockReturnLineServiceInterface;
use Modules\Returns\Application\Contracts\DeleteStockReturnServiceInterface;
use Modules\Returns\Application\Contracts\FailQualityCheckServiceInterface;
use Modules\Returns\Application\Contracts\FindStockReturnLineServiceInterface;
use Modules\Returns\Application\Contracts\FindStockReturnServiceInterface;
use Modules\Returns\Application\Contracts\IssueCreditMemoServiceInterface;
use Modules\Returns\Application\Contracts\PassQualityCheckServiceInterface;
use Modules\Returns\Application\Contracts\ProcessReturnInventoryAdjustmentServiceInterface;
use Modules\Returns\Application\Contracts\RejectStockReturnServiceInterface;
use Modules\Returns\Application\Contracts\UpdateStockReturnLineServiceInterface;
use Modules\Returns\Application\Contracts\UpdateStockReturnServiceInterface;
use Modules\Returns\Application\Services\ApproveStockReturnService;
use Modules\Returns\Application\Services\CancelStockReturnService;
use Modules\Returns\Application\Services\CompleteStockReturnService;
use Modules\Returns\Application\Services\CreateStockReturnLineService;
use Modules\Returns\Application\Services\CreateStockReturnService;
use Modules\Returns\Application\Services\DeleteStockReturnLineService;
use Modules\Returns\Application\Services\DeleteStockReturnService;
use Modules\Returns\Application\Services\FailQualityCheckService;
use Modules\Returns\Application\Services\FindStockReturnLineService;
use Modules\Returns\Application\Services\FindStockReturnService;
use Modules\Returns\Application\Services\IssueCreditMemoService;
use Modules\Returns\Application\Services\PassQualityCheckService;
use Modules\Returns\Application\Services\ProcessReturnInventoryAdjustmentService;
use Modules\Returns\Application\Services\RejectStockReturnService;
use Modules\Returns\Application\Services\UpdateStockReturnLineService;
use Modules\Returns\Application\Services\UpdateStockReturnService;
use Modules\Returns\Application\Contracts\ApplyCreditMemoServiceInterface;
use Modules\Returns\Application\Contracts\ApproveReturnAuthorizationServiceInterface;
use Modules\Returns\Application\Contracts\CancelReturnAuthorizationServiceInterface;
use Modules\Returns\Application\Contracts\CreateCreditMemoServiceInterface;
use Modules\Returns\Application\Contracts\CreateReturnAuthorizationServiceInterface;
use Modules\Returns\Application\Contracts\DeleteCreditMemoServiceInterface;
use Modules\Returns\Application\Contracts\DeleteReturnAuthorizationServiceInterface;
use Modules\Returns\Application\Contracts\ExpireReturnAuthorizationServiceInterface;
use Modules\Returns\Application\Contracts\FindCreditMemoServiceInterface;
use Modules\Returns\Application\Contracts\FindReturnAuthorizationServiceInterface;
use Modules\Returns\Application\Contracts\IssueCreditMemoDocumentServiceInterface;
use Modules\Returns\Application\Contracts\UpdateReturnAuthorizationServiceInterface;
use Modules\Returns\Application\Contracts\VoidCreditMemoServiceInterface;
use Modules\Returns\Application\Services\ApplyCreditMemoService;
use Modules\Returns\Application\Services\ApproveReturnAuthorizationService;
use Modules\Returns\Application\Services\CancelReturnAuthorizationService;
use Modules\Returns\Application\Services\CreateCreditMemoService;
use Modules\Returns\Application\Services\CreateReturnAuthorizationService;
use Modules\Returns\Application\Services\DeleteCreditMemoService;
use Modules\Returns\Application\Services\DeleteReturnAuthorizationService;
use Modules\Returns\Application\Services\ExpireReturnAuthorizationService;
use Modules\Returns\Application\Services\FindCreditMemoService;
use Modules\Returns\Application\Services\FindReturnAuthorizationService;
use Modules\Returns\Application\Services\IssueCreditMemoDocumentService;
use Modules\Returns\Application\Services\UpdateReturnAuthorizationService;
use Modules\Returns\Application\Services\VoidCreditMemoService;
use Modules\Returns\Domain\RepositoryInterfaces\CreditMemoRepositoryInterface;
use Modules\Returns\Domain\RepositoryInterfaces\ReturnAuthorizationRepositoryInterface;
use Modules\Returns\Domain\RepositoryInterfaces\StockReturnLineRepositoryInterface;
use Modules\Returns\Domain\RepositoryInterfaces\StockReturnRepositoryInterface;
use Modules\Returns\Infrastructure\Persistence\Eloquent\Models\CreditMemoModel;
use Modules\Returns\Infrastructure\Persistence\Eloquent\Models\ReturnAuthorizationModel;
use Modules\Returns\Infrastructure\Persistence\Eloquent\Models\StockReturnLineModel;
use Modules\Returns\Infrastructure\Persistence\Eloquent\Models\StockReturnModel;
use Modules\Returns\Infrastructure\Persistence\Eloquent\Repositories\EloquentCreditMemoRepository;
use Modules\Returns\Infrastructure\Persistence\Eloquent\Repositories\EloquentReturnAuthorizationRepository;
use Modules\Returns\Infrastructure\Persistence\Eloquent\Repositories\EloquentStockReturnLineRepository;
use Modules\Returns\Infrastructure\Persistence\Eloquent\Repositories\EloquentStockReturnRepository;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLevelRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventorySettingRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryValuationLayerRepositoryInterface;
use Modules\StockMovement\Domain\RepositoryInterfaces\StockMovementRepositoryInterface;

class ReturnsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // --- Repositories ---
        $this->app->bind(StockReturnRepositoryInterface::class, fn ($app) =>
            new EloquentStockReturnRepository($app->make(StockReturnModel::class)));

        $this->app->bind(StockReturnLineRepositoryInterface::class, fn ($app) =>
            new EloquentStockReturnLineRepository($app->make(StockReturnLineModel::class)));

        // --- Services: StockReturn ---
        $this->app->bind(CreateStockReturnServiceInterface::class, fn ($app) =>
            new CreateStockReturnService($app->make(StockReturnRepositoryInterface::class)));

        $this->app->bind(FindStockReturnServiceInterface::class, fn ($app) =>
            new FindStockReturnService($app->make(StockReturnRepositoryInterface::class)));

        $this->app->bind(UpdateStockReturnServiceInterface::class, fn ($app) =>
            new UpdateStockReturnService($app->make(StockReturnRepositoryInterface::class)));

        $this->app->bind(DeleteStockReturnServiceInterface::class, fn ($app) =>
            new DeleteStockReturnService($app->make(StockReturnRepositoryInterface::class)));

        $this->app->bind(ApproveStockReturnServiceInterface::class, fn ($app) =>
            new ApproveStockReturnService($app->make(StockReturnRepositoryInterface::class)));

        $this->app->bind(RejectStockReturnServiceInterface::class, fn ($app) =>
            new RejectStockReturnService($app->make(StockReturnRepositoryInterface::class)));

        $this->app->bind(CompleteStockReturnServiceInterface::class, fn ($app) =>
            new CompleteStockReturnService(
                $app->make(StockReturnRepositoryInterface::class),
                $app->make(ProcessReturnInventoryAdjustmentServiceInterface::class),
            ));

        $this->app->bind(ProcessReturnInventoryAdjustmentServiceInterface::class, fn ($app) =>
            new ProcessReturnInventoryAdjustmentService(
                $app->make(StockReturnRepositoryInterface::class),
                $app->make(StockReturnLineRepositoryInterface::class),
                $app->make(StockMovementRepositoryInterface::class),
                $app->make(InventoryLevelRepositoryInterface::class),
                $app->make(InventoryValuationLayerRepositoryInterface::class),
                $app->make(InventorySettingRepositoryInterface::class),
            ));

        $this->app->bind(CancelStockReturnServiceInterface::class, fn ($app) =>
            new CancelStockReturnService($app->make(StockReturnRepositoryInterface::class)));

        $this->app->bind(IssueCreditMemoServiceInterface::class, fn ($app) =>
            new IssueCreditMemoService($app->make(StockReturnRepositoryInterface::class)));

        // --- Services: StockReturnLine ---
        $this->app->bind(CreateStockReturnLineServiceInterface::class, fn ($app) =>
            new CreateStockReturnLineService($app->make(StockReturnLineRepositoryInterface::class)));

        $this->app->bind(FindStockReturnLineServiceInterface::class, fn ($app) =>
            new FindStockReturnLineService($app->make(StockReturnLineRepositoryInterface::class)));

        $this->app->bind(UpdateStockReturnLineServiceInterface::class, fn ($app) =>
            new UpdateStockReturnLineService($app->make(StockReturnLineRepositoryInterface::class)));

        $this->app->bind(DeleteStockReturnLineServiceInterface::class, fn ($app) =>
            new DeleteStockReturnLineService($app->make(StockReturnLineRepositoryInterface::class)));

        $this->app->bind(PassQualityCheckServiceInterface::class, fn ($app) =>
            new PassQualityCheckService($app->make(StockReturnLineRepositoryInterface::class)));

        $this->app->bind(FailQualityCheckServiceInterface::class, fn ($app) =>
            new FailQualityCheckService($app->make(StockReturnLineRepositoryInterface::class)));
        // --- Repositories: CreditMemo ---
        $this->app->bind(CreditMemoRepositoryInterface::class, fn ($app) =>
            new EloquentCreditMemoRepository($app->make(CreditMemoModel::class)));

        // --- Services: CreditMemo ---
        $this->app->bind(CreateCreditMemoServiceInterface::class, fn ($app) =>
            new CreateCreditMemoService($app->make(CreditMemoRepositoryInterface::class)));

        $this->app->bind(FindCreditMemoServiceInterface::class, fn ($app) =>
            new FindCreditMemoService($app->make(CreditMemoRepositoryInterface::class)));

        $this->app->bind(IssueCreditMemoDocumentServiceInterface::class, fn ($app) =>
            new IssueCreditMemoDocumentService($app->make(CreditMemoRepositoryInterface::class)));

        $this->app->bind(ApplyCreditMemoServiceInterface::class, fn ($app) =>
            new ApplyCreditMemoService($app->make(CreditMemoRepositoryInterface::class)));

        $this->app->bind(VoidCreditMemoServiceInterface::class, fn ($app) =>
            new VoidCreditMemoService($app->make(CreditMemoRepositoryInterface::class)));

        $this->app->bind(DeleteCreditMemoServiceInterface::class, fn ($app) =>
            new DeleteCreditMemoService($app->make(CreditMemoRepositoryInterface::class)));

        // --- Repositories: ReturnAuthorization ---
        $this->app->bind(ReturnAuthorizationRepositoryInterface::class, fn ($app) =>
            new EloquentReturnAuthorizationRepository($app->make(ReturnAuthorizationModel::class)));

        // --- Services: ReturnAuthorization ---
        $this->app->bind(CreateReturnAuthorizationServiceInterface::class, fn ($app) =>
            new CreateReturnAuthorizationService($app->make(ReturnAuthorizationRepositoryInterface::class)));

        $this->app->bind(FindReturnAuthorizationServiceInterface::class, fn ($app) =>
            new FindReturnAuthorizationService($app->make(ReturnAuthorizationRepositoryInterface::class)));

        $this->app->bind(ApproveReturnAuthorizationServiceInterface::class, fn ($app) =>
            new ApproveReturnAuthorizationService($app->make(ReturnAuthorizationRepositoryInterface::class)));

        $this->app->bind(CancelReturnAuthorizationServiceInterface::class, fn ($app) =>
            new CancelReturnAuthorizationService($app->make(ReturnAuthorizationRepositoryInterface::class)));

        $this->app->bind(ExpireReturnAuthorizationServiceInterface::class, fn ($app) =>
            new ExpireReturnAuthorizationService($app->make(ReturnAuthorizationRepositoryInterface::class)));

        $this->app->bind(DeleteReturnAuthorizationServiceInterface::class, fn ($app) =>
            new DeleteReturnAuthorizationService($app->make(ReturnAuthorizationRepositoryInterface::class)));

        $this->app->bind(UpdateReturnAuthorizationServiceInterface::class, fn ($app) =>
            new UpdateReturnAuthorizationService($app->make(ReturnAuthorizationRepositoryInterface::class)));
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
