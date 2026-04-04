<?php
namespace Modules\User\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\User\Application\Contracts\ChangePasswordServiceInterface;
use Modules\User\Application\Contracts\CreateUserServiceInterface;
use Modules\User\Application\Contracts\UpdateProfileServiceInterface;
use Modules\User\Application\Contracts\UploadAvatarServiceInterface;
use Modules\User\Application\Services\ChangePasswordService;
use Modules\User\Application\Services\CreateUserService;
use Modules\User\Application\Services\UpdateProfileService;
use Modules\User\Application\Services\UploadAvatarService;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRepository;

class UserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(CreateUserServiceInterface::class, CreateUserService::class);
        $this->app->bind(UpdateProfileServiceInterface::class, UpdateProfileService::class);
        $this->app->bind(ChangePasswordServiceInterface::class, ChangePasswordService::class);
        $this->app->bind(UploadAvatarServiceInterface::class, UploadAvatarService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
