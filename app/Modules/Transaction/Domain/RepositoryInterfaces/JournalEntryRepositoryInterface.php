<?php

declare(strict_types=1);

namespace Modules\Transaction\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Transaction\Domain\Entities\JournalEntry;

interface JournalEntryRepositoryInterface extends RepositoryInterface
{
    public function save(JournalEntry $journalEntry): JournalEntry;

    public function findById(int $id): ?JournalEntry;

    public function findByTransaction(int $transactionId): Collection;

    public function findByAccountCode(int $tenantId, string $accountCode): Collection;

    public function list(array $filters = [], int $perPage = 15, int $page = 1): mixed;
}
