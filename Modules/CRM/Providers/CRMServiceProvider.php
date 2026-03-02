<?php

declare(strict_types=1);

namespace Modules\CRM\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\CRM\Application\Handlers\ConvertLeadHandler;
use Modules\CRM\Application\Handlers\CreateLeadHandler;
use Modules\CRM\Application\Handlers\UpdateLeadHandler;
use Modules\CRM\Domain\Contracts\LeadRepositoryInterface;
use Modules\CRM\Domain\Contracts\OpportunityRepositoryInterface;
use Modules\CRM\Infrastructure\Repositories\LeadRepository;
use Modules\CRM\Infrastructure\Repositories\OpportunityRepository;

class CRMServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LeadRepositoryInterface::class, LeadRepository::class);
        $this->app->bind(OpportunityRepositoryInterface::class, OpportunityRepository::class);

        $this->app->singleton(CreateLeadHandler::class);
        $this->app->singleton(UpdateLeadHandler::class);
        $this->app->singleton(ConvertLeadHandler::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Infrastructure/Database/Migrations');
    }
}

