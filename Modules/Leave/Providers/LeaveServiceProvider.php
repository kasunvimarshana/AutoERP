<?php

namespace Modules\Leave\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Leave\Domain\Contracts\LeaveAllocationRepositoryInterface;
use Modules\Leave\Domain\Contracts\LeaveRequestRepositoryInterface;
use Modules\Leave\Domain\Contracts\LeaveTypeRepositoryInterface;
use Modules\Leave\Infrastructure\Repositories\LeaveAllocationRepository;
use Modules\Leave\Infrastructure\Repositories\LeaveRequestRepository;
use Modules\Leave\Infrastructure\Repositories\LeaveTypeRepository;

class LeaveServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LeaveTypeRepositoryInterface::class, LeaveTypeRepository::class);
        $this->app->bind(LeaveRequestRepositoryInterface::class, LeaveRequestRepository::class);
        $this->app->bind(LeaveAllocationRepositoryInterface::class, LeaveAllocationRepository::class);
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'leave');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
    }
}
