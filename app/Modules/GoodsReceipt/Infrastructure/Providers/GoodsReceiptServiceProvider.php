<?php
namespace Modules\GoodsReceipt\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\GoodsReceipt\Application\Contracts\CompleteGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\CreateGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\InspectGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\PutAwayGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\Services\CompleteGoodsReceiptService;
use Modules\GoodsReceipt\Application\Services\CreateGoodsReceiptService;
use Modules\GoodsReceipt\Application\Services\InspectGoodsReceiptService;
use Modules\GoodsReceipt\Application\Services\PutAwayGoodsReceiptService;
use Modules\GoodsReceipt\Domain\RepositoryInterfaces\GoodsReceiptLineRepositoryInterface;
use Modules\GoodsReceipt\Domain\RepositoryInterfaces\GoodsReceiptRepositoryInterface;
use Modules\GoodsReceipt\Infrastructure\Persistence\Eloquent\Repositories\EloquentGoodsReceiptLineRepository;
use Modules\GoodsReceipt\Infrastructure\Persistence\Eloquent\Repositories\EloquentGoodsReceiptRepository;

class GoodsReceiptServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(GoodsReceiptRepositoryInterface::class, EloquentGoodsReceiptRepository::class);
        $this->app->bind(GoodsReceiptLineRepositoryInterface::class, EloquentGoodsReceiptLineRepository::class);
        $this->app->bind(CreateGoodsReceiptServiceInterface::class, CreateGoodsReceiptService::class);
        $this->app->bind(InspectGoodsReceiptServiceInterface::class, InspectGoodsReceiptService::class);
        $this->app->bind(PutAwayGoodsReceiptServiceInterface::class, PutAwayGoodsReceiptService::class);
        $this->app->bind(CompleteGoodsReceiptServiceInterface::class, CompleteGoodsReceiptService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
