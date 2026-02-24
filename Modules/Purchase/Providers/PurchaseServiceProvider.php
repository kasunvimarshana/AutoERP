<?php
namespace Modules\Purchase\Providers;
use Illuminate\Support\ServiceProvider;
use Modules\Purchase\Domain\Contracts\VendorRepositoryInterface;
use Modules\Purchase\Domain\Contracts\PurchaseOrderRepositoryInterface;
use Modules\Purchase\Domain\Contracts\GoodsReceiptRepositoryInterface;
use Modules\Purchase\Domain\Contracts\PurchaseRequisitionRepositoryInterface;
use Modules\Purchase\Infrastructure\Repositories\VendorRepository;
use Modules\Purchase\Infrastructure\Repositories\PurchaseOrderRepository;
use Modules\Purchase\Infrastructure\Repositories\GoodsReceiptRepository;
use Modules\Purchase\Infrastructure\Repositories\PurchaseRequisitionRepository;
class PurchaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(VendorRepositoryInterface::class, VendorRepository::class);
        $this->app->bind(PurchaseOrderRepositoryInterface::class, PurchaseOrderRepository::class);
        $this->app->bind(GoodsReceiptRepositoryInterface::class, GoodsReceiptRepository::class);
        $this->app->bind(PurchaseRequisitionRepositoryInterface::class, PurchaseRequisitionRepository::class);
        $this->mergeConfigFrom(__DIR__.'/../config.php', 'purchase');
    }
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
