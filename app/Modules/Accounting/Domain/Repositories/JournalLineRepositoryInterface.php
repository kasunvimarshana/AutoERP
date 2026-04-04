<?php
namespace Modules\Accounting\Domain\Repositories;

use Modules\Accounting\Domain\Entities\JournalLine;

interface JournalLineRepositoryInterface
{
    public function findById(int $id): ?JournalLine;
    public function findByEntry(int $entryId): array;
    public function create(array $data): JournalLine;
    public function delete(JournalLine $line): bool;
}
