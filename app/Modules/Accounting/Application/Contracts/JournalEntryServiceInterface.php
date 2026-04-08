<?php
declare(strict_types=1);
namespace Modules\Accounting\Application\Contracts;
use Modules\Accounting\Domain\Entities\JournalEntry;
interface JournalEntryServiceInterface {
    public function createEntry(string $tenantId, array $data, array $lines): JournalEntry;
    public function postEntry(string $tenantId, string $id): JournalEntry;
    public function voidEntry(string $tenantId, string $id): JournalEntry;
    public function getEntry(string $tenantId, string $id): JournalEntry;
    public function getAllEntries(string $tenantId, array $filters = []): array;
}
