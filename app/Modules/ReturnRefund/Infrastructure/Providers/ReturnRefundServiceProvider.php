<?php

declare(strict_types=1);

namespace Modules\ReturnRefund\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\ReturnRefund\Application\Contracts\ProcessReturnAndRefundServiceInterface;
use Modules\ReturnRefund\Application\Services\ProcessReturnAndRefundService;
use Modules\ReturnRefund\Domain\RepositoryInterfaces\RefundTransactionRepositoryInterface;
use Modules\ReturnRefund\Domain\RepositoryInterfaces\ReturnInspectionRepositoryInterface;
use Modules\ReturnRefund\Infrastructure\Persistence\Eloquent\Repositories\EloquentRefundTransactionRepository;
use Modules\ReturnRefund\Infrastructure\Persistence\Eloquent\Repositories\EloquentReturnInspectionRepository;

class ReturnRefundServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $this->app->bind(ReturnInspectionRepositoryInterface::class, EloquentReturnInspectionRepository::class);
        $this->app->bind(RefundTransactionRepositoryInterface::class, EloquentRefundTransactionRepository::class);
        $this->app->bind(ProcessReturnAndRefundServiceInterface::class, ProcessReturnAndRefundService::class);
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__.'/../../routes/api.php',
            __DIR__.'/../../database/migrations',
        );
    }
}
