<?php
declare(strict_types=1);
namespace Modules\GoodsReceipt\Infrastructure\Providers;
use Illuminate\Support\ServiceProvider;
use Modules\GoodsReceipt\Application\Contracts\InspectGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\PutAwayGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\Services\InspectGoodsReceiptService;
use Modules\GoodsReceipt\Application\Services\PutAwayGoodsReceiptService;
use Modules\GoodsReceipt\Domain\RepositoryInterfaces\GoodsReceiptRepositoryInterface;
use Modules\GoodsReceipt\Infrastructure\Persistence\Eloquent\Models\GoodsReceiptLineModel;
use Modules\GoodsReceipt\Infrastructure\Persistence\Eloquent\Models\GoodsReceiptModel;
use Modules\GoodsReceipt\Infrastructure\Persistence\Eloquent\Repositories\EloquentGoodsReceiptRepository;
class GoodsReceiptServiceProvider extends ServiceProvider {
    public function register(): void {
        $this->app->bind(GoodsReceiptRepositoryInterface::class, fn($app) =>
            new EloquentGoodsReceiptRepository($app->make(GoodsReceiptModel::class),$app->make(GoodsReceiptLineModel::class))
        );
        $this->app->bind(InspectGoodsReceiptServiceInterface::class, fn($app) => new InspectGoodsReceiptService($app->make(GoodsReceiptRepositoryInterface::class)));
        $this->app->bind(PutAwayGoodsReceiptServiceInterface::class, fn($app) => new PutAwayGoodsReceiptService($app->make(GoodsReceiptRepositoryInterface::class)));
    }
    public function boot(): void {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
