<?php
namespace Modules\CRM\Providers;
use Illuminate\Support\ServiceProvider;
use Modules\CRM\Domain\Contracts\LeadRepositoryInterface;
use Modules\CRM\Domain\Contracts\OpportunityRepositoryInterface;
use Modules\CRM\Domain\Contracts\ContactRepositoryInterface;
use Modules\CRM\Domain\Contracts\ActivityRepositoryInterface;
use Modules\CRM\Infrastructure\Repositories\LeadRepository;
use Modules\CRM\Infrastructure\Repositories\OpportunityRepository;
use Modules\CRM\Infrastructure\Repositories\ContactRepository;
use Modules\CRM\Infrastructure\Repositories\ActivityRepository;
class CRMServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LeadRepositoryInterface::class, LeadRepository::class);
        $this->app->bind(OpportunityRepositoryInterface::class, OpportunityRepository::class);
        $this->app->bind(ContactRepositoryInterface::class, ContactRepository::class);
        $this->app->bind(ActivityRepositoryInterface::class, ActivityRepository::class);
        $this->mergeConfigFrom(__DIR__.'/../config.php', 'crm');
    }
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
