<?php
declare(strict_types=1);
namespace Modules\Accounting\Domain\RepositoryInterfaces;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Accounting\Domain\Entities\JournalEntry;
interface JournalEntryRepositoryInterface {
    public function findById(int $id): ?JournalEntry;
    public function findByTenant(int $tenantId, array $filters = [], int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function create(array $data, array $lines): JournalEntry;
    public function update(int $id, array $data): ?JournalEntry;
    public function delete(int $id): bool;
}
