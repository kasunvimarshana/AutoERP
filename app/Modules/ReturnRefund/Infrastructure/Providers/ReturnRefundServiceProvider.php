<?php

declare(strict_types=1);

namespace Modules\ReturnRefund\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\ReturnRefund\Application\Contracts\ReturnRefundServiceInterface;
use Modules\ReturnRefund\Application\Services\ReturnRefundService;
use Modules\ReturnRefund\Domain\RepositoryInterfaces\ReturnRefundRepositoryInterface;
use Modules\ReturnRefund\Infrastructure\Persistence\Eloquent\Repositories\EloquentReturnRefundRepository;

class ReturnRefundServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $this->app->bind(ReturnRefundRepositoryInterface::class, EloquentReturnRefundRepository::class);
        $this->app->bind(ReturnRefundServiceInterface::class, ReturnRefundService::class);
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__ . '/../../routes/api.php',
            __DIR__ . '/../../database/migrations',
        );
    }
}
