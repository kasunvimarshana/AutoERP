<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\Finance\Application\Contracts\CreateAccountServiceInterface;
use Modules\Finance\Application\Contracts\CreateFiscalPeriodServiceInterface;
use Modules\Finance\Application\Contracts\CreateFiscalYearServiceInterface;
use Modules\Finance\Application\Contracts\CreateJournalEntryServiceInterface;
use Modules\Finance\Application\Contracts\DeleteAccountServiceInterface;
use Modules\Finance\Application\Contracts\DeleteFiscalPeriodServiceInterface;
use Modules\Finance\Application\Contracts\DeleteFiscalYearServiceInterface;
use Modules\Finance\Application\Contracts\DeleteJournalEntryServiceInterface;
use Modules\Finance\Application\Contracts\FindAccountServiceInterface;
use Modules\Finance\Application\Contracts\FindFiscalPeriodServiceInterface;
use Modules\Finance\Application\Contracts\FindFiscalYearServiceInterface;
use Modules\Finance\Application\Contracts\FindJournalEntryServiceInterface;
use Modules\Finance\Application\Contracts\PostJournalEntryServiceInterface;
use Modules\Finance\Application\Contracts\UpdateAccountServiceInterface;
use Modules\Finance\Application\Contracts\UpdateFiscalPeriodServiceInterface;
use Modules\Finance\Application\Contracts\UpdateFiscalYearServiceInterface;
use Modules\Finance\Application\Contracts\UpdateJournalEntryServiceInterface;
use Modules\Finance\Application\Services\CreateAccountService;
use Modules\Finance\Application\Services\CreateFiscalPeriodService;
use Modules\Finance\Application\Services\CreateFiscalYearService;
use Modules\Finance\Application\Services\CreateJournalEntryService;
use Modules\Finance\Application\Services\DeleteAccountService;
use Modules\Finance\Application\Services\DeleteFiscalPeriodService;
use Modules\Finance\Application\Services\DeleteFiscalYearService;
use Modules\Finance\Application\Services\DeleteJournalEntryService;
use Modules\Finance\Application\Services\FindAccountService;
use Modules\Finance\Application\Services\FindFiscalPeriodService;
use Modules\Finance\Application\Services\FindFiscalYearService;
use Modules\Finance\Application\Services\FindJournalEntryService;
use Modules\Finance\Application\Services\PostJournalEntryService;
use Modules\Finance\Application\Services\UpdateAccountService;
use Modules\Finance\Application\Services\UpdateFiscalPeriodService;
use Modules\Finance\Application\Services\UpdateFiscalYearService;
use Modules\Finance\Application\Services\UpdateJournalEntryService;
use Modules\Finance\Domain\RepositoryInterfaces\AccountRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\FiscalPeriodRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\FiscalYearRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\JournalEntryRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentAccountRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentFiscalPeriodRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentFiscalYearRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentJournalEntryRepository;

class FinanceServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $repositoryBindings = [
            AccountRepositoryInterface::class => EloquentAccountRepository::class,
            FiscalPeriodRepositoryInterface::class => EloquentFiscalPeriodRepository::class,
            FiscalYearRepositoryInterface::class => EloquentFiscalYearRepository::class,
            JournalEntryRepositoryInterface::class => EloquentJournalEntryRepository::class,
        ];

        foreach ($repositoryBindings as $contract => $implementation) {
            $this->app->bind($contract, $implementation);
        }

        $serviceBindings = [
            CreateAccountServiceInterface::class => CreateAccountService::class,
            FindAccountServiceInterface::class => FindAccountService::class,
            UpdateAccountServiceInterface::class => UpdateAccountService::class,
            DeleteAccountServiceInterface::class => DeleteAccountService::class,
            CreateFiscalYearServiceInterface::class => CreateFiscalYearService::class,
            FindFiscalYearServiceInterface::class => FindFiscalYearService::class,
            UpdateFiscalYearServiceInterface::class => UpdateFiscalYearService::class,
            DeleteFiscalYearServiceInterface::class => DeleteFiscalYearService::class,
            CreateFiscalPeriodServiceInterface::class => CreateFiscalPeriodService::class,
            FindFiscalPeriodServiceInterface::class => FindFiscalPeriodService::class,
            UpdateFiscalPeriodServiceInterface::class => UpdateFiscalPeriodService::class,
            DeleteFiscalPeriodServiceInterface::class => DeleteFiscalPeriodService::class,
            CreateJournalEntryServiceInterface::class => CreateJournalEntryService::class,
            FindJournalEntryServiceInterface::class => FindJournalEntryService::class,
            UpdateJournalEntryServiceInterface::class => UpdateJournalEntryService::class,
            DeleteJournalEntryServiceInterface::class => DeleteJournalEntryService::class,
            PostJournalEntryServiceInterface::class => PostJournalEntryService::class,
        ];

        foreach ($serviceBindings as $contract => $implementation) {
            $this->app->bind($contract, $implementation);
        }
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__.'/../../routes/api.php',
            __DIR__.'/../../database/migrations',
        );
    }
}
