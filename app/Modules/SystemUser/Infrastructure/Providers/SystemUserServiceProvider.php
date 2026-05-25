<?php

declare(strict_types=1);

namespace Modules\SystemUser\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\SystemUser\Application\Contracts\UseCases\SystemUsers\CreateSystemUserServiceInterface;
use Modules\SystemUser\Application\Contracts\UseCases\SystemUsers\DeleteSystemUserServiceInterface;
use Modules\SystemUser\Application\Contracts\UseCases\SystemUsers\GetSystemUserServiceInterface;
use Modules\SystemUser\Application\Contracts\UseCases\SystemUsers\ListSystemUsersServiceInterface;
use Modules\SystemUser\Application\Contracts\UseCases\SystemUsers\UpdateSystemUserServiceInterface;
use Modules\SystemUser\Application\Repositories\SystemUserRepositoryInterface;
use Modules\SystemUser\Application\UseCases\SystemUsers\CreateSystemUserService;
use Modules\SystemUser\Application\UseCases\SystemUsers\DeleteSystemUserService;
use Modules\SystemUser\Application\UseCases\SystemUsers\GetSystemUserService;
use Modules\SystemUser\Application\UseCases\SystemUsers\ListSystemUsersService;
use Modules\SystemUser\Application\UseCases\SystemUsers\UpdateSystemUserService;
use Modules\SystemUser\Domain\Contracts\SystemUserDomainServiceInterface;
use Modules\SystemUser\Domain\Services\SystemUserDomainService;
use Modules\SystemUser\Infrastructure\Persistence\Eloquent\Models\SystemUserModel;
use Modules\SystemUser\Infrastructure\Persistence\Eloquent\Repositories\EloquentSystemUserRepository;

final class SystemUserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/system-user.php', 'system-user');

        $this->app->singleton(SystemUserDomainServiceInterface::class, SystemUserDomainService::class);

        foreach (
            [
                ListSystemUsersServiceInterface::class => ListSystemUsersService::class,
                GetSystemUserServiceInterface::class => GetSystemUserService::class,
                CreateSystemUserServiceInterface::class => CreateSystemUserService::class,
                UpdateSystemUserServiceInterface::class => UpdateSystemUserService::class,
                DeleteSystemUserServiceInterface::class => DeleteSystemUserService::class,
            ] as $contract => $implementation
        ) {
            $this->app->singleton($contract, $implementation);
        }

        $this->app->singleton(SystemUserRepositoryInterface::class, function (): SystemUserRepositoryInterface {
            return new EloquentSystemUserRepository(new SystemUserModel());
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../Infrastructure/Persistence/Eloquent/Migrations');
    }
}
