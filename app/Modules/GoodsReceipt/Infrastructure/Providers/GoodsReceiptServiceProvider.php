<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\GoodsReceipt\Application\Contracts\ApproveGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\CancelGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\CreateGoodsReceiptLineServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\CreateGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\DeleteGoodsReceiptLineServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\DeleteGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\FindGoodsReceiptLineServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\FindGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\InspectGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\PutAwayGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\ReceiveGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\UpdateGoodsReceiptLineServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\UpdateGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\Services\ApproveGoodsReceiptService;
use Modules\GoodsReceipt\Application\Services\CancelGoodsReceiptService;
use Modules\GoodsReceipt\Application\Services\CreateGoodsReceiptLineService;
use Modules\GoodsReceipt\Application\Services\CreateGoodsReceiptService;
use Modules\GoodsReceipt\Application\Services\DeleteGoodsReceiptLineService;
use Modules\GoodsReceipt\Application\Services\DeleteGoodsReceiptService;
use Modules\GoodsReceipt\Application\Services\FindGoodsReceiptLineService;
use Modules\GoodsReceipt\Application\Services\FindGoodsReceiptService;
use Modules\GoodsReceipt\Application\Services\InspectGoodsReceiptService;
use Modules\GoodsReceipt\Application\Services\PutAwayGoodsReceiptService;
use Modules\GoodsReceipt\Application\Services\ReceiveGoodsReceiptService;
use Modules\GoodsReceipt\Application\Services\UpdateGoodsReceiptLineService;
use Modules\GoodsReceipt\Application\Services\UpdateGoodsReceiptService;
use Modules\GoodsReceipt\Domain\RepositoryInterfaces\GoodsReceiptLineRepositoryInterface;
use Modules\GoodsReceipt\Domain\RepositoryInterfaces\GoodsReceiptRepositoryInterface;
use Modules\GoodsReceipt\Infrastructure\Persistence\Eloquent\Models\GoodsReceiptLineModel;
use Modules\GoodsReceipt\Infrastructure\Persistence\Eloquent\Models\GoodsReceiptModel;
use Modules\GoodsReceipt\Infrastructure\Persistence\Eloquent\Repositories\EloquentGoodsReceiptLineRepository;
use Modules\GoodsReceipt\Infrastructure\Persistence\Eloquent\Repositories\EloquentGoodsReceiptRepository;

class GoodsReceiptServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // --- Repositories ---
        $this->app->bind(GoodsReceiptRepositoryInterface::class, fn ($app) =>
            new EloquentGoodsReceiptRepository($app->make(GoodsReceiptModel::class)));

        $this->app->bind(GoodsReceiptLineRepositoryInterface::class, fn ($app) =>
            new EloquentGoodsReceiptLineRepository($app->make(GoodsReceiptLineModel::class)));

        // --- Services: GoodsReceipt ---
        $this->app->bind(CreateGoodsReceiptServiceInterface::class, fn ($app) =>
            new CreateGoodsReceiptService($app->make(GoodsReceiptRepositoryInterface::class)));

        $this->app->bind(FindGoodsReceiptServiceInterface::class, fn ($app) =>
            new FindGoodsReceiptService($app->make(GoodsReceiptRepositoryInterface::class)));

        $this->app->bind(UpdateGoodsReceiptServiceInterface::class, fn ($app) =>
            new UpdateGoodsReceiptService($app->make(GoodsReceiptRepositoryInterface::class)));

        $this->app->bind(DeleteGoodsReceiptServiceInterface::class, fn ($app) =>
            new DeleteGoodsReceiptService($app->make(GoodsReceiptRepositoryInterface::class)));

        $this->app->bind(ReceiveGoodsReceiptServiceInterface::class, fn ($app) =>
            new ReceiveGoodsReceiptService($app->make(GoodsReceiptRepositoryInterface::class)));

        $this->app->bind(ApproveGoodsReceiptServiceInterface::class, fn ($app) =>
            new ApproveGoodsReceiptService($app->make(GoodsReceiptRepositoryInterface::class)));

        $this->app->bind(CancelGoodsReceiptServiceInterface::class, fn ($app) =>
            new CancelGoodsReceiptService($app->make(GoodsReceiptRepositoryInterface::class)));

        $this->app->bind(InspectGoodsReceiptServiceInterface::class, fn ($app) =>
            new InspectGoodsReceiptService($app->make(GoodsReceiptRepositoryInterface::class)));

        $this->app->bind(PutAwayGoodsReceiptServiceInterface::class, fn ($app) =>
            new PutAwayGoodsReceiptService($app->make(GoodsReceiptRepositoryInterface::class)));

        // --- Services: GoodsReceiptLine ---
        $this->app->bind(CreateGoodsReceiptLineServiceInterface::class, fn ($app) =>
            new CreateGoodsReceiptLineService($app->make(GoodsReceiptLineRepositoryInterface::class)));

        $this->app->bind(FindGoodsReceiptLineServiceInterface::class, fn ($app) =>
            new FindGoodsReceiptLineService($app->make(GoodsReceiptLineRepositoryInterface::class)));

        $this->app->bind(UpdateGoodsReceiptLineServiceInterface::class, fn ($app) =>
            new UpdateGoodsReceiptLineService($app->make(GoodsReceiptLineRepositoryInterface::class)));

        $this->app->bind(DeleteGoodsReceiptLineServiceInterface::class, fn ($app) =>
            new DeleteGoodsReceiptLineService($app->make(GoodsReceiptLineRepositoryInterface::class)));
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
