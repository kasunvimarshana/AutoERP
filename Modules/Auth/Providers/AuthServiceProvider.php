<?php
namespace Modules\Auth\Providers;
use Illuminate\Support\ServiceProvider;
use Modules\Auth\Domain\Contracts\AuthServiceInterface;
use Modules\Auth\Infrastructure\Services\SanctumAuthService;
class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuthServiceInterface::class, SanctumAuthService::class);
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'auth_module');
    }
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
    }
}
