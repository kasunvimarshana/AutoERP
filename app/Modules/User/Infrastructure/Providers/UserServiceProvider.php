<?php
declare(strict_types=1);
namespace Modules\User\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\User\Application\Contracts\ChangePasswordServiceInterface;
use Modules\User\Application\Contracts\CreateUserServiceInterface;
use Modules\User\Application\Contracts\DeleteUserServiceInterface;
use Modules\User\Application\Contracts\GetUserServiceInterface;
use Modules\User\Application\Contracts\UpdateProfileServiceInterface;
use Modules\User\Application\Contracts\UpdateUserServiceInterface;
use Modules\User\Application\Contracts\UploadAvatarServiceInterface;
use Modules\User\Application\Services\ChangePasswordService;
use Modules\User\Application\Services\CreateUserService;
use Modules\User\Application\Services\DeleteUserService;
use Modules\User\Application\Services\GetUserService;
use Modules\User\Application\Services\UpdateProfileService;
use Modules\User\Application\Services\UpdateUserService;
use Modules\User\Application\Services\UploadAvatarService;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRepository;

class UserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, fn($app) =>
            new EloquentUserRepository($app->make(UserModel::class))
        );
        $this->app->bind(CreateUserServiceInterface::class, fn($app) =>
            new CreateUserService($app->make(UserRepositoryInterface::class))
        );
        $this->app->bind(UpdateUserServiceInterface::class, fn($app) =>
            new UpdateUserService($app->make(UserRepositoryInterface::class))
        );
        $this->app->bind(DeleteUserServiceInterface::class, fn($app) =>
            new DeleteUserService($app->make(UserRepositoryInterface::class))
        );
        $this->app->bind(GetUserServiceInterface::class, fn($app) =>
            new GetUserService($app->make(UserRepositoryInterface::class))
        );
        $this->app->bind(UpdateProfileServiceInterface::class, fn($app) =>
            new UpdateProfileService($app->make(UserRepositoryInterface::class))
        );
        $this->app->bind(ChangePasswordServiceInterface::class, fn($app) =>
            new ChangePasswordService($app->make(UserRepositoryInterface::class))
        );
        $this->app->bind(UploadAvatarServiceInterface::class, fn($app) =>
            new UploadAvatarService(
                $app->make(UserRepositoryInterface::class),
                $app->make(FileStorageServiceInterface::class)
            )
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
