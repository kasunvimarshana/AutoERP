<?php

namespace App\Providers;

use App\Domain\Contracts\TenantRepositoryInterface;
use App\Domain\Contracts\TokenRepositoryInterface;
use App\Domain\Contracts\UserRepositoryInterface;
use App\Repositories\TenantRepository;
use App\Repositories\TokenRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * All repository interface-to-implementation bindings.
     */
    public array $bindings = [
        UserRepositoryInterface::class   => UserRepository::class,
        TenantRepositoryInterface::class => TenantRepository::class,
        TokenRepositoryInterface::class  => TokenRepository::class,
    ];

    public function register(): void
    {
        foreach ($this->bindings as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }
}
