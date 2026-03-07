<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            UserRepositoryInterface::class,
            fn (): UserRepository => new UserRepository(new User())
        );
    }

    public function boot(): void {}
}
