<?php
declare(strict_types=1);
namespace Modules\Supplier\Infrastructure\Providers;
use Illuminate\Support\ServiceProvider;
use Modules\Supplier\Application\Contracts\SupplierServiceInterface;
use Modules\Supplier\Application\Services\SupplierService;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierRepositoryInterface;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories\EloquentSupplierRepository;
class SupplierServiceProvider extends ServiceProvider {
    public function register(): void {
        $this->app->bind(SupplierRepositoryInterface::class, fn($app) =>
            new EloquentSupplierRepository($app->make(SupplierModel::class))
        );
        $this->app->bind(SupplierServiceInterface::class, fn($app) =>
            new SupplierService($app->make(SupplierRepositoryInterface::class))
        );
    }
    public function boot(): void {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
