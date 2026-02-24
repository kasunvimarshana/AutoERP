<?php
namespace Modules\Tenant\Providers;
use Illuminate\Support\ServiceProvider;
use Modules\Tenant\Domain\Contracts\TenantRepositoryInterface;
use Modules\Tenant\Domain\Contracts\TenantResolverInterface;
use Modules\Tenant\Infrastructure\Repositories\TenantRepository;
use Modules\Tenant\Application\Services\ResolveTenantService;
class TenantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TenantRepositoryInterface::class, TenantRepository::class);
        $this->app->bind(TenantResolverInterface::class, ResolveTenantService::class);
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'tenant');
    }
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
    }
}
