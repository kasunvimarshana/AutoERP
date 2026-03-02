<?php

declare(strict_types=1);

namespace Modules\Sales\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Sales\Application\Handlers\CreateSaleHandler;
use Modules\Sales\Domain\Contracts\ContactRepositoryInterface;
use Modules\Sales\Domain\Contracts\SaleRepositoryInterface;
use Modules\Sales\Infrastructure\Repositories\ContactRepository;
use Modules\Sales\Infrastructure\Repositories\SaleRepository;

class SalesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SaleRepositoryInterface::class, SaleRepository::class);
        $this->app->bind(ContactRepositoryInterface::class, ContactRepository::class);
        $this->app->singleton(CreateSaleHandler::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Infrastructure/Database/Migrations');
    }
}

