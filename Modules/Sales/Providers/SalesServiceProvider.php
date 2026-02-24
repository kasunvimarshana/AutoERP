<?php
namespace Modules\Sales\Providers;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\CRM\Domain\Events\LeadConverted;
use Modules\Sales\Application\Listeners\HandleLeadConvertedListener;
use Modules\Sales\Domain\Contracts\CustomerRepositoryInterface;
use Modules\Sales\Domain\Contracts\PriceListRepositoryInterface;
use Modules\Sales\Domain\Contracts\QuotationRepositoryInterface;
use Modules\Sales\Domain\Contracts\SalesOrderRepositoryInterface;
use Modules\Sales\Infrastructure\Repositories\CustomerRepository;
use Modules\Sales\Infrastructure\Repositories\PriceListRepository;
use Modules\Sales\Infrastructure\Repositories\QuotationRepository;
use Modules\Sales\Infrastructure\Repositories\SalesOrderRepository;
class SalesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CustomerRepositoryInterface::class, CustomerRepository::class);
        $this->app->bind(QuotationRepositoryInterface::class, QuotationRepository::class);
        $this->app->bind(SalesOrderRepositoryInterface::class, SalesOrderRepository::class);
        $this->app->bind(PriceListRepositoryInterface::class, PriceListRepository::class);
        $this->mergeConfigFrom(__DIR__.'/../config.php', 'sales');
    }
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
        Event::listen(LeadConverted::class, HandleLeadConvertedListener::class);
    }
}
