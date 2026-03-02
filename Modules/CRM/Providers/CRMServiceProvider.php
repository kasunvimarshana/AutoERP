<?php

declare(strict_types=1);

namespace Modules\Crm\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Crm\Application\Services\ActivityService;
use Modules\Crm\Application\Services\ContactService;
use Modules\Crm\Application\Services\LeadService;
use Modules\Crm\Domain\Contracts\ActivityRepositoryInterface;
use Modules\Crm\Domain\Contracts\ContactRepositoryInterface;
use Modules\Crm\Domain\Contracts\LeadRepositoryInterface;
use Modules\Crm\Infrastructure\Repositories\ActivityRepository;
use Modules\Crm\Infrastructure\Repositories\ContactRepository;
use Modules\Crm\Infrastructure\Repositories\LeadRepository;

class CrmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ContactRepositoryInterface::class, ContactRepository::class);
        $this->app->bind(LeadRepositoryInterface::class, LeadRepository::class);
        $this->app->bind(ActivityRepositoryInterface::class, ActivityRepository::class);

        $this->app->singleton(ActivityService::class);
        $this->app->singleton(ContactService::class);
        $this->app->singleton(LeadService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Infrastructure/Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Interfaces/Http/routes.php');
    }
}
