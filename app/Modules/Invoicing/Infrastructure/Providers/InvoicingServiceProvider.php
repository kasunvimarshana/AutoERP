<?php

declare(strict_types=1);

namespace Modules\Invoicing\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\Invoicing\Application\Contracts\InvoiceServiceInterface;
use Modules\Invoicing\Application\Services\InvoiceService;
use Modules\Invoicing\Domain\RepositoryInterfaces\InvoiceRepositoryInterface;
use Modules\Invoicing\Infrastructure\Persistence\Eloquent\Repositories\EloquentInvoiceRepository;

class InvoicingServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $this->app->bind(InvoiceRepositoryInterface::class, EloquentInvoiceRepository::class);
        $this->app->bind(InvoiceServiceInterface::class, InvoiceService::class);
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__ . '/../../routes/api.php',
            __DIR__ . '/../../database/migrations',
        );
    }
}
