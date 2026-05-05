<?php

declare(strict_types=1);

namespace Modules\Receipts\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\Receipts\Application\Contracts\ReceiptServiceInterface;
use Modules\Receipts\Application\Services\ReceiptService;
use Modules\Receipts\Domain\RepositoryInterfaces\ReceiptRepositoryInterface;
use Modules\Receipts\Infrastructure\Persistence\Eloquent\Repositories\EloquentReceiptRepository;

class ReceiptsServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $this->app->bind(ReceiptRepositoryInterface::class, EloquentReceiptRepository::class);
        $this->app->bind(ReceiptServiceInterface::class, ReceiptService::class);
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__ . '/../../routes/api.php',
            __DIR__ . '/../../database/migrations',
        );
    }
}
