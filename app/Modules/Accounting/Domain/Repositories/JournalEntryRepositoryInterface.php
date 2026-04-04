<?php
namespace Modules\Accounting\Domain\Repositories;

use Modules\Accounting\Domain\Entities\JournalEntry;
use Modules\Accounting\Domain\Entities\JournalLine;

interface JournalEntryRepositoryInterface
{
    public function findById(int $id): ?JournalEntry;
    public function findByReference(int $tenantId, string $ref): ?JournalEntry;
    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator;
    public function create(array $data): JournalEntry;
    public function addLine(int $entryId, array $lineData): JournalLine;
    public function findLines(int $entryId): array;
    public function update(JournalEntry $entry, array $data): JournalEntry;
    public function delete(JournalEntry $entry): bool;
}
