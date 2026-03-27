<?php

declare(strict_types=1);

namespace Modules\Account\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Account\Application\Contracts\CreateAccountServiceInterface;
use Modules\Account\Application\Contracts\DeleteAccountServiceInterface;
use Modules\Account\Application\Contracts\UpdateAccountServiceInterface;
use Modules\Account\Application\Services\CreateAccountService;
use Modules\Account\Application\Services\DeleteAccountService;
use Modules\Account\Application\Services\UpdateAccountService;
use Modules\Account\Domain\RepositoryInterfaces\AccountRepositoryInterface;
use Modules\Account\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use Modules\Account\Infrastructure\Persistence\Eloquent\Repositories\EloquentAccountRepository;

class AccountServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AccountRepositoryInterface::class, function ($app) {
            return new EloquentAccountRepository($app->make(AccountModel::class));
        });

        $this->app->bind(CreateAccountServiceInterface::class, function ($app) {
            return new CreateAccountService($app->make(AccountRepositoryInterface::class));
        });

        $this->app->bind(UpdateAccountServiceInterface::class, function ($app) {
            return new UpdateAccountService($app->make(AccountRepositoryInterface::class));
        });

        $this->app->bind(DeleteAccountServiceInterface::class, function ($app) {
            return new DeleteAccountService($app->make(AccountRepositoryInterface::class));
        });
    }

    public function boot(): void
    {
        Route::middleware('api')
             ->prefix('api')
             ->group(function () {
                 $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
             });

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
