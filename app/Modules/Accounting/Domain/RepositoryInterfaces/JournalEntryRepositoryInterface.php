<?php
declare(strict_types=1);
namespace Modules\Accounting\Domain\RepositoryInterfaces;
use Modules\Accounting\Domain\Entities\JournalEntry;
interface JournalEntryRepositoryInterface {
    public function findById(string $tenantId, string $id): ?JournalEntry;
    public function findByNumber(string $tenantId, string $number): ?JournalEntry;
    public function findAll(string $tenantId, array $filters = []): array;
    public function save(JournalEntry $entry): void;
    public function delete(string $tenantId, string $id): void;
}
