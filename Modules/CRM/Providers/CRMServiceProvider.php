<?php

declare(strict_types=1);

namespace Modules\CRM\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\CRM\Models\Customer;
use Modules\CRM\Models\Lead;
use Modules\CRM\Models\Opportunity;
use Modules\CRM\Policies\CustomerPolicy;
use Modules\CRM\Policies\LeadPolicy;
use Modules\CRM\Policies\OpportunityPolicy;

class CRMServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register module configuration
        $this->mergeConfigFrom(__DIR__.'/../config/crm.php', 'crm');

        // Register repositories
        $this->app->singleton(\Modules\CRM\Repositories\CustomerRepository::class);
        $this->app->singleton(\Modules\CRM\Repositories\ContactRepository::class);
        $this->app->singleton(\Modules\CRM\Repositories\LeadRepository::class);
        $this->app->singleton(\Modules\CRM\Repositories\OpportunityRepository::class);

        // Register services
        $this->app->singleton(\Modules\CRM\Services\CustomerService::class);
        $this->app->singleton(\Modules\CRM\Services\LeadConversionService::class);
        $this->app->singleton(\Modules\CRM\Services\OpportunityService::class);
    }

    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        // Register policies
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Lead::class, LeadPolicy::class);
        Gate::policy(Opportunity::class, OpportunityPolicy::class);

        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/crm.php' => config_path('crm.php'),
        ], 'crm-config');
    }
}
