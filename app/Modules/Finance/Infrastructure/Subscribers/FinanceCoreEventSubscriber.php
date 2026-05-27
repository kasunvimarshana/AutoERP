<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Subscribers;

use Illuminate\Contracts\Events\Dispatcher;
use Modules\Finance\Infrastructure\Listeners\GenerateJournalEntryFromEventListener;

final class FinanceCoreEventSubscriber
{
    public function subscribe(Dispatcher $events): void
    {
        $listener = GenerateJournalEntryFromEventListener::class;

        foreach ((array) config('finance.core.events.generate_journal_from', []) as $eventName) {
            if (is_string($eventName) && trim($eventName) !== '') {
                $events->listen($eventName, $listener);
            }
        }
    }
}
