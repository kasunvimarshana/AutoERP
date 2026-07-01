<?php

declare(strict_types=1);

namespace Modules\Finance\Tests;

use PHPUnit\Framework\TestCase;

final class FinancePostingIdempotencyBoundaryTest extends TestCase
{
    public function test_source_posting_uses_unique_identity_and_fingerprint(): void
    {
        $posting = $this->source('../Services/FinancePostingService.php');
        $creation = $this->source('../Services/JournalEntryCreationService.php');
        $data = $this->source('../DTOs/CreateJournalEntryData.php');
        $migration = $this->source('../Database/Migrations/2026_06_12_070009_create_finance_journal_entries_table.php');

        self::assertStringContainsString('sourceKey:', $posting);
        self::assertStringContainsString('postingFingerprint:', $posting);
        self::assertStringContainsString('->where(\'source_key\', $sourceKey)', $posting);
        self::assertStringContainsString('assertReplayMatches', $posting);
        self::assertStringContainsString('posting_fingerprint', $creation);
        self::assertStringContainsString('public ?string $sourceKey', $data);
        self::assertStringContainsString('public ?string $postingFingerprint', $data);
        self::assertStringContainsString('$table->unique(\'source_key\'', $migration);
    }

    public function test_posted_or_reversed_journal_replay_does_not_repost_ledger(): void
    {
        $posting = $this->source('../Services/JournalPostingService.php');
        $statusGuard = 'in_array($status, [JournalStatus::Posted, JournalStatus::Reversed], true)';

        self::assertStringContainsString($statusGuard, $posting);
        self::assertStringContainsString('return $this->resultFromJournal($journal, $status);', $posting);
        self::assertStringContainsString('$ledgerCount = $this->ledger->post($journal);', $posting);
        self::assertLessThan(
            strpos($posting, '$ledgerCount = $this->ledger->post($journal);'),
            strpos($posting, $statusGuard),
        );
    }

    public function test_conflicting_replay_fails_instead_of_creating_another_journal(): void
    {
        $posting = $this->source('../Services/FinancePostingService.php');

        self::assertStringContainsString('hash_equals($stored, $fingerprint)', $posting);
        self::assertStringContainsString('Finance posting source was already used with different posting facts.', $posting);
        self::assertStringNotContainsString('suspense', strtolower($posting));
    }

    private function source(string $relativePath): string
    {
        $source = file_get_contents(__DIR__.'/'.$relativePath);
        self::assertIsString($source);

        return $source;
    }
}
