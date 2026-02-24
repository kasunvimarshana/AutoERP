<?php
namespace Modules\Inventory\Providers;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Inventory\Application\Commands\ProcessReorderRulesCommand;
use Modules\Inventory\Application\Listeners\HandleDeliveryCompletedListener;
use Modules\Inventory\Application\Listeners\HandleECommerceOrderConfirmedListener;
use Modules\Inventory\Application\Listeners\HandleGoodsReceivedListener;
use Modules\Inventory\Application\Listeners\HandlePosOrderPlacedListener;
use Modules\Inventory\Application\Listeners\HandleWorkOrderCompletedListener;
use Modules\Inventory\Domain\Contracts\ProductRepositoryInterface;
use Modules\Inventory\Domain\Contracts\WarehouseRepositoryInterface;
use Modules\Inventory\Domain\Contracts\StockMovementRepositoryInterface;
use Modules\Inventory\Domain\Contracts\StockLevelRepositoryInterface;
use Modules\Inventory\Domain\Contracts\InventoryValuationRepositoryInterface;
use Modules\Inventory\Domain\Contracts\InventoryLotRepositoryInterface;
use Modules\Inventory\Infrastructure\Repositories\ProductRepository;
use Modules\Inventory\Infrastructure\Repositories\WarehouseRepository;
use Modules\Inventory\Infrastructure\Repositories\StockMovementRepository;
use Modules\Inventory\Infrastructure\Repositories\StockLevelRepository;
use Modules\Inventory\Infrastructure\Repositories\InventoryValuationRepository;
use Modules\Inventory\Infrastructure\Repositories\InventoryLotRepository;
use Modules\Inventory\Domain\Contracts\CycleCountRepositoryInterface;
use Modules\Inventory\Infrastructure\Repositories\CycleCountRepository;
use Modules\Inventory\Domain\Contracts\ProductVariantRepositoryInterface;
use Modules\Inventory\Infrastructure\Repositories\ProductVariantRepository;
use Modules\Logistics\Domain\Events\DeliveryCompleted;
use Modules\Manufacturing\Domain\Events\WorkOrderCompleted;
use Modules\POS\Domain\Events\PosOrderPlaced;
use Modules\Purchase\Domain\Events\GoodsReceived;
use Modules\ECommerce\Domain\Events\ECommerceOrderConfirmed;
class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(WarehouseRepositoryInterface::class, WarehouseRepository::class);
        $this->app->bind(StockMovementRepositoryInterface::class, StockMovementRepository::class);
        $this->app->bind(StockLevelRepositoryInterface::class, StockLevelRepository::class);
        $this->app->bind(InventoryValuationRepositoryInterface::class, InventoryValuationRepository::class);
        $this->app->bind(InventoryLotRepositoryInterface::class, InventoryLotRepository::class);
        $this->app->bind(CycleCountRepositoryInterface::class, CycleCountRepository::class);
        $this->app->bind(ProductVariantRepositoryInterface::class, ProductVariantRepository::class);
        $this->mergeConfigFrom(__DIR__.'/../config.php', 'inventory');
    }
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
        if ($this->app->runningInConsole()) {
            $this->commands([ProcessReorderRulesCommand::class]);
        }

        // Cross-module event listeners: react to POS sales, purchase receipts, production completions, delivery completions, and e-commerce order confirmations
        Event::listen(PosOrderPlaced::class, HandlePosOrderPlacedListener::class);
        Event::listen(GoodsReceived::class, HandleGoodsReceivedListener::class);
        Event::listen(WorkOrderCompleted::class, HandleWorkOrderCompletedListener::class);
        Event::listen(DeliveryCompleted::class, HandleDeliveryCompletedListener::class);
        Event::listen(ECommerceOrderConfirmed::class, HandleECommerceOrderConfirmedListener::class);
    }
}
