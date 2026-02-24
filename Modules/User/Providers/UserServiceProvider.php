<?php
namespace Modules\User\Providers;
use Illuminate\Support\ServiceProvider;
use Modules\User\Domain\Contracts\UserRepositoryInterface;
use Modules\User\Domain\Contracts\RoleRepositoryInterface;
use Modules\User\Infrastructure\Repositories\UserRepository;
use Modules\User\Infrastructure\Repositories\RoleRepository;
class UserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(RoleRepositoryInterface::class, RoleRepository::class);
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'user');
    }
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
    }
}
