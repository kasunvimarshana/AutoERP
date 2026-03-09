<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Contracts\AuthServiceInterface;
use App\Domain\Contracts\UserRepositoryInterface;
use App\Infrastructure\Database\Repositories\UserRepository;
use App\Infrastructure\Services\AuthService;
use Illuminate\Support\ServiceProvider;

/**
 * Repository Service Provider
 *
 * Binds interfaces to concrete implementations.
 * Use this provider to swap implementations without modifying consuming code.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
        );

        // Service bindings
        $this->app->bind(
            AuthServiceInterface::class,
            AuthService::class
        );
    }
}
