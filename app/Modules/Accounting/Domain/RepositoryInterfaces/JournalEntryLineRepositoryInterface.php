<?php
declare(strict_types=1);
namespace Modules\Accounting\Domain\RepositoryInterfaces;
use Modules\Accounting\Domain\Entities\JournalEntryLine;
interface JournalEntryLineRepositoryInterface {
    public function findByJournalEntry(string $tenantId, string $entryId): array;
    public function save(JournalEntryLine $line): void;
    public function deleteByJournalEntry(string $tenantId, string $entryId): void;
}
