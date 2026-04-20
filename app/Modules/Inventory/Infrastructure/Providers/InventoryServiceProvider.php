<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\Inventory\Application\Contracts\FindStockLevelServiceInterface;
use Modules\Inventory\Application\Contracts\FindStockMovementServiceInterface;
use Modules\Inventory\Application\Contracts\FindTransferOrderServiceInterface;
use Modules\Inventory\Application\Contracts\RecordStockMovementServiceInterface;
use Modules\Inventory\Application\Contracts\ApproveTransferOrderServiceInterface;
use Modules\Inventory\Application\Contracts\CompleteCycleCountServiceInterface;
use Modules\Inventory\Application\Contracts\CreateStockReservationServiceInterface;
use Modules\Inventory\Application\Contracts\CreateCycleCountServiceInterface;
use Modules\Inventory\Application\Contracts\CreateTransferOrderServiceInterface;
use Modules\Inventory\Application\Contracts\FindCycleCountServiceInterface;
use Modules\Inventory\Application\Contracts\FindStockReservationServiceInterface;
use Modules\Inventory\Application\Contracts\ReceiveTransferOrderServiceInterface;
use Modules\Inventory\Application\Contracts\ReleaseStockReservationServiceInterface;
use Modules\Inventory\Application\Contracts\ReleaseExpiredStockReservationsServiceInterface;
use Modules\Inventory\Application\Contracts\StartCycleCountServiceInterface;
use Modules\Inventory\Application\Services\CompleteCycleCountService;
use Modules\Inventory\Application\Services\CreateCycleCountService;
use Modules\Inventory\Application\Services\CreateStockReservationService;
use Modules\Inventory\Application\Services\ApproveTransferOrderService;
use Modules\Inventory\Application\Services\CreateTransferOrderService;
use Modules\Inventory\Application\Services\FindCycleCountService;
use Modules\Inventory\Application\Services\FindStockLevelService;
use Modules\Inventory\Application\Services\FindStockMovementService;
use Modules\Inventory\Application\Services\FindStockReservationService;
use Modules\Inventory\Application\Services\FindTransferOrderService;
use Modules\Inventory\Application\Services\RecordStockMovementService;
use Modules\Inventory\Application\Services\ReleaseStockReservationService;
use Modules\Inventory\Application\Services\ReleaseExpiredStockReservationsService;
use Modules\Inventory\Application\Services\StartCycleCountService;
use Modules\Inventory\Domain\RepositoryInterfaces\CycleCountRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryStockRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\StockReservationRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\TraceLogRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\TransferOrderRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentCycleCountRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventoryStockRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentStockReservationRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentTraceLogRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentTransferOrderRepository;
use Modules\Inventory\Application\Services\ReceiveTransferOrderService;
use Modules\Inventory\Infrastructure\Console\Commands\ReleaseExpiredStockReservationsCommand;

class InventoryServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $this->app->bind(InventoryStockRepositoryInterface::class, EloquentInventoryStockRepository::class);
        $this->app->bind(TransferOrderRepositoryInterface::class, EloquentTransferOrderRepository::class);
        $this->app->bind(CycleCountRepositoryInterface::class, EloquentCycleCountRepository::class);
        $this->app->bind(TraceLogRepositoryInterface::class, EloquentTraceLogRepository::class);
        $this->app->bind(StockReservationRepositoryInterface::class, EloquentStockReservationRepository::class);

        $this->app->bind(RecordStockMovementServiceInterface::class, RecordStockMovementService::class);
        $this->app->bind(FindStockMovementServiceInterface::class, FindStockMovementService::class);
        $this->app->bind(FindStockLevelServiceInterface::class, FindStockLevelService::class);

        $this->app->bind(CreateTransferOrderServiceInterface::class, CreateTransferOrderService::class);
        $this->app->bind(FindTransferOrderServiceInterface::class, FindTransferOrderService::class);
        $this->app->bind(ApproveTransferOrderServiceInterface::class, ApproveTransferOrderService::class);
        $this->app->bind(ReceiveTransferOrderServiceInterface::class, ReceiveTransferOrderService::class);

        $this->app->bind(CreateCycleCountServiceInterface::class, CreateCycleCountService::class);
        $this->app->bind(FindCycleCountServiceInterface::class, FindCycleCountService::class);
        $this->app->bind(StartCycleCountServiceInterface::class, StartCycleCountService::class);
        $this->app->bind(CompleteCycleCountServiceInterface::class, CompleteCycleCountService::class);

        $this->app->bind(CreateStockReservationServiceInterface::class, CreateStockReservationService::class);
        $this->app->bind(FindStockReservationServiceInterface::class, FindStockReservationService::class);
        $this->app->bind(ReleaseStockReservationServiceInterface::class, ReleaseStockReservationService::class);
        $this->app->bind(ReleaseExpiredStockReservationsServiceInterface::class, ReleaseExpiredStockReservationsService::class);
    }

    public function boot(): void
    {
        $this->commands([
            ReleaseExpiredStockReservationsCommand::class,
        ]);

        $this->bootModule(
            __DIR__.'/../../routes/api.php',
            __DIR__.'/../../database/migrations',
        );
    }
}
