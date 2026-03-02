<?php

declare(strict_types=1);

namespace Modules\Accounting\Domain\Contracts;

use Modules\Accounting\Domain\Entities\JournalEntry;

interface JournalEntryRepositoryInterface
{
    public function nextEntryNumber(int $tenantId): string;

    public function findById(int $id, int $tenantId): ?JournalEntry;

    public function findAll(int $tenantId, int $page, int $perPage): array;

    public function save(JournalEntry $entry): JournalEntry;

    public function delete(int $id, int $tenantId): void;
}
