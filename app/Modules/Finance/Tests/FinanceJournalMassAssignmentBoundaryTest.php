<?php

declare(strict_types=1);

namespace Modules\Finance\Tests;

use Modules\Finance\Models\FinanceJournalEntry;
use PHPUnit\Framework\TestCase;

final class FinanceJournalMassAssignmentBoundaryTest extends TestCase
{
    public function test_journal_is_totally_guarded_and_owner_service_writes_explicitly(): void
    {
        $journal = new FinanceJournalEntry();
        $service = file_get_contents(dirname(__DIR__).'/Services/JournalEntryCreationService.php');

        self::assertSame(['*'], $journal->getGuarded());
        self::assertFalse($journal->isFillable('status'));
        self::assertIsString($service);
        self::assertStringContainsString('$journal = new FinanceJournalEntry();', $service);
        self::assertStringContainsString('$journal->forceFill([', $service);
        self::assertStringNotContainsString('FinanceJournalEntry::query()->create([', $service);
    }
}
