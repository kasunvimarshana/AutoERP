<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Finance\Application\Contracts\UseCases\FinanceCore\CloseFiscalPeriodServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\FinanceCore\GenerateJournalEntryFromEventServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\FinanceCore\PostJournalToLedgerServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\FinanceCore\RecalculateLedgerBalancesServiceInterface;
use Modules\Finance\Application\Repositories\FinanceProcessedEventRepositoryInterface;
use Modules\Finance\Application\Repositories\LedgerEntryRepositoryInterface;
use Modules\Finance\Application\UseCases\FinanceCore\CloseFiscalPeriodService;
use Modules\Finance\Application\UseCases\FinanceCore\GenerateJournalEntryFromEventService;
use Modules\Finance\Application\UseCases\FinanceCore\PostJournalToLedgerService;
use Modules\Finance\Application\UseCases\FinanceCore\RecalculateLedgerBalancesService;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\FinanceProcessedEventModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\LedgerEntryModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentFinanceProcessedEventRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentLedgerEntryRepository;
use Modules\Finance\Infrastructure\Subscribers\FinanceCoreEventSubscriber;

final class FinanceCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach (
            [
                GenerateJournalEntryFromEventServiceInterface::class => GenerateJournalEntryFromEventService::class,
                PostJournalToLedgerServiceInterface::class => PostJournalToLedgerService::class,
                CloseFiscalPeriodServiceInterface::class => CloseFiscalPeriodService::class,
                RecalculateLedgerBalancesServiceInterface::class => RecalculateLedgerBalancesService::class,
            ] as $contract => $implementation
        ) {
            $this->app->singleton($contract, $implementation);
        }

        $this->app->singleton(LedgerEntryRepositoryInterface::class, function (): LedgerEntryRepositoryInterface {
            return new EloquentLedgerEntryRepository(new LedgerEntryModel());
        });

        $this->app->singleton(
            FinanceProcessedEventRepositoryInterface::class,
            function (): FinanceProcessedEventRepositoryInterface {
                return new EloquentFinanceProcessedEventRepository(new FinanceProcessedEventModel());
            },
        );
    }

    public function boot(): void
    {
        Event::subscribe(FinanceCoreEventSubscriber::class);
    }
}
