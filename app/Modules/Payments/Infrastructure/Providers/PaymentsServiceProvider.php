<?php

declare(strict_types=1);

namespace Modules\Payments\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\Payments\Application\Contracts\PaymentServiceInterface;
use Modules\Payments\Application\Services\PaymentService;
use Modules\Payments\Domain\RepositoryInterfaces\PaymentRepositoryInterface;
use Modules\Payments\Infrastructure\Persistence\Eloquent\Repositories\EloquentPaymentRepository;

class PaymentsServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $this->app->bind(PaymentRepositoryInterface::class, EloquentPaymentRepository::class);
        $this->app->bind(PaymentServiceInterface::class, PaymentService::class);
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__ . '/../../routes/api.php',
            __DIR__ . '/../../database/migrations',
        );
    }
}
