<?php
declare(strict_types=1);
namespace Modules\Procurement\Providers;
use Illuminate\Support\ServiceProvider;
use Modules\Procurement\Application\Handlers\CreatePurchaseOrderHandler;
use Modules\Procurement\Domain\Contracts\PurchaseRepositoryInterface;
use Modules\Procurement\Infrastructure\Repositories\PurchaseRepository;
class ProcurementServiceProvider extends ServiceProvider {
    public function register(): void {
        $this->app->bind(PurchaseRepositoryInterface::class, PurchaseRepository::class);
        $this->app->singleton(CreatePurchaseOrderHandler::class);
    }
    public function boot(): void {
        $this->loadMigrationsFrom(__DIR__.'/../Infrastructure/Database/Migrations');
    }
}
