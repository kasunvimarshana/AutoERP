<?php

namespace Modules\Invoice\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Invoice\Application\Repositories\InvoiceLineRepositoryInterface;
use Modules\Invoice\Application\Repositories\InvoiceReferenceRepositoryInterface;
use Modules\Invoice\Application\Repositories\InvoiceRepositoryInterface;
use Modules\Invoice\Infrastructure\Persistence\Eloquent\Repositories\EloquentInvoiceLineRepository;
use Modules\Invoice\Infrastructure\Persistence\Eloquent\Repositories\EloquentInvoiceReferenceRepository;
use Modules\Invoice\Infrastructure\Persistence\Eloquent\Repositories\EloquentInvoiceRepository;

class InvoiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/invoice.php', 'invoice');

        foreach ([
            InvoiceLineRepositoryInterface::class => EloquentInvoiceLineRepository::class,
            InvoiceRepositoryInterface::class => EloquentInvoiceRepository::class,
            InvoiceReferenceRepositoryInterface::class => EloquentInvoiceReferenceRepository::class,
        ] as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../Infrastructure/Persistence/Eloquent/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
