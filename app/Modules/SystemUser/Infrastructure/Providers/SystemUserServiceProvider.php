<?php

declare(strict_types=1);

namespace Modules\SystemUser\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\SystemUser\Application\Repositories\SystemUserRepositoryInterface;
use Modules\SystemUser\Infrastructure\Persistence\Eloquent\Repositories\EloquentSystemUserRepository;

class SystemUserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach (
            [
                SystemUserRepositoryInterface::class => EloquentSystemUserRepository::class,
            ] as $interface => $implementation
        ) {
            $this->app->bind($interface, $implementation);
        }
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../Infrastructure/Persistence/Eloquent/Migrations');
    }
}
