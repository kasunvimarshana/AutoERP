<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\Finance\Application\Contracts\CreateAccountServiceInterface;
use Modules\Finance\Application\Contracts\CreateJournalEntryServiceInterface;
use Modules\Finance\Application\Contracts\CreateFiscalPeriodServiceInterface;
use Modules\Finance\Application\Contracts\CreateFiscalYearServiceInterface;
use Modules\Finance\Application\Contracts\DeleteAccountServiceInterface;
use Modules\Finance\Application\Contracts\DeleteFiscalPeriodServiceInterface;
use Modules\Finance\Application\Contracts\DeleteFiscalYearServiceInterface;
use Modules\Finance\Application\Contracts\DeleteJournalEntryServiceInterface;
use Modules\Finance\Application\Contracts\FindFiscalPeriodServiceInterface;
use Modules\Finance\Application\Contracts\FindFiscalYearServiceInterface;
use Modules\Finance\Application\Contracts\FindAccountServiceInterface;
use Modules\Finance\Application\Contracts\FindJournalEntryServiceInterface;
use Modules\Finance\Application\Contracts\PostJournalEntryServiceInterface;
use Modules\Finance\Application\Contracts\UpdateFiscalPeriodServiceInterface;
use Modules\Finance\Application\Contracts\UpdateFiscalYearServiceInterface;
use Modules\Finance\Application\Contracts\UpdateAccountServiceInterface;
use Modules\Finance\Application\Contracts\UpdateJournalEntryServiceInterface;
use Modules\Finance\Application\Services\CreateAccountService;
use Modules\Finance\Application\Services\CreateFiscalPeriodService;
use Modules\Finance\Application\Services\CreateFiscalYearService;
use Modules\Finance\Application\Services\DeleteFiscalPeriodService;
use Modules\Finance\Application\Services\DeleteFiscalYearService;
use Modules\Finance\Application\Services\CreateJournalEntryService;
use Modules\Finance\Application\Services\DeleteAccountService;
use Modules\Finance\Application\Services\DeleteJournalEntryService;
use Modules\Finance\Application\Services\FindFiscalPeriodService;
use Modules\Finance\Application\Services\FindFiscalYearService;
use Modules\Finance\Application\Services\FindAccountService;
use Modules\Finance\Application\Services\FindJournalEntryService;
use Modules\Finance\Application\Services\PostJournalEntryService;
use Modules\Finance\Application\Services\UpdateFiscalPeriodService;
use Modules\Finance\Application\Services\UpdateFiscalYearService;
use Modules\Finance\Application\Services\UpdateAccountService;
use Modules\Finance\Application\Services\UpdateJournalEntryService;
use Modules\Finance\Domain\RepositoryInterfaces\AccountRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\FiscalPeriodRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\FiscalYearRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\JournalEntryRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\FiscalPeriodModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\FiscalYearModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\JournalEntryModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentAccountRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentFiscalPeriodRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentFiscalYearRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentJournalEntryRepository;

class FinanceServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $this->app->bind(AccountRepositoryInterface::class, function ($app) {
            return new EloquentAccountRepository($app->make(AccountModel::class));
        });

        $this->app->bind(FiscalPeriodRepositoryInterface::class, function ($app) {
            return new EloquentFiscalPeriodRepository($app->make(FiscalPeriodModel::class));
        });

        $this->app->bind(FiscalYearRepositoryInterface::class, function ($app) {
            return new EloquentFiscalYearRepository($app->make(FiscalYearModel::class));
        });

        $this->app->bind(JournalEntryRepositoryInterface::class, function ($app) {
            return new EloquentJournalEntryRepository($app->make(JournalEntryModel::class));
        });

        $this->app->bind(CreateAccountServiceInterface::class, function ($app) {
            return new CreateAccountService($app->make(AccountRepositoryInterface::class));
        });

        $this->app->bind(FindAccountServiceInterface::class, function ($app) {
            return new FindAccountService($app->make(AccountRepositoryInterface::class));
        });

        $this->app->bind(UpdateAccountServiceInterface::class, function ($app) {
            return new UpdateAccountService($app->make(AccountRepositoryInterface::class));
        });

        $this->app->bind(DeleteAccountServiceInterface::class, function ($app) {
            return new DeleteAccountService($app->make(AccountRepositoryInterface::class));
        });

        $this->app->bind(CreateFiscalYearServiceInterface::class, function ($app) {
            return new CreateFiscalYearService($app->make(FiscalYearRepositoryInterface::class));
        });

        $this->app->bind(FindFiscalYearServiceInterface::class, function ($app) {
            return new FindFiscalYearService($app->make(FiscalYearRepositoryInterface::class));
        });

        $this->app->bind(UpdateFiscalYearServiceInterface::class, function ($app) {
            return new UpdateFiscalYearService($app->make(FiscalYearRepositoryInterface::class));
        });

        $this->app->bind(DeleteFiscalYearServiceInterface::class, function ($app) {
            return new DeleteFiscalYearService($app->make(FiscalYearRepositoryInterface::class));
        });

        $this->app->bind(CreateFiscalPeriodServiceInterface::class, function ($app) {
            return new CreateFiscalPeriodService(
                $app->make(FiscalPeriodRepositoryInterface::class),
                $app->make(FiscalYearRepositoryInterface::class),
            );
        });

        $this->app->bind(FindFiscalPeriodServiceInterface::class, function ($app) {
            return new FindFiscalPeriodService($app->make(FiscalPeriodRepositoryInterface::class));
        });

        $this->app->bind(UpdateFiscalPeriodServiceInterface::class, function ($app) {
            return new UpdateFiscalPeriodService(
                $app->make(FiscalPeriodRepositoryInterface::class),
                $app->make(FiscalYearRepositoryInterface::class),
            );
        });

        $this->app->bind(DeleteFiscalPeriodServiceInterface::class, function ($app) {
            return new DeleteFiscalPeriodService($app->make(FiscalPeriodRepositoryInterface::class));
        });

        $this->app->bind(CreateJournalEntryServiceInterface::class, function ($app) {
            return new CreateJournalEntryService(
                $app->make(JournalEntryRepositoryInterface::class),
                $app->make(FiscalPeriodRepositoryInterface::class),
            );
        });

        $this->app->bind(FindJournalEntryServiceInterface::class, function ($app) {
            return new FindJournalEntryService($app->make(JournalEntryRepositoryInterface::class));
        });

        $this->app->bind(UpdateJournalEntryServiceInterface::class, function ($app) {
            return new UpdateJournalEntryService(
                $app->make(JournalEntryRepositoryInterface::class),
                $app->make(FiscalPeriodRepositoryInterface::class),
            );
        });

        $this->app->bind(DeleteJournalEntryServiceInterface::class, function ($app) {
            return new DeleteJournalEntryService($app->make(JournalEntryRepositoryInterface::class));
        });

        $this->app->bind(PostJournalEntryServiceInterface::class, function ($app) {
            return new PostJournalEntryService($app->make(JournalEntryRepositoryInterface::class));
        });
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__.'/../../routes/api.php',
            __DIR__.'/../../database/migrations',
        );
    }
}
