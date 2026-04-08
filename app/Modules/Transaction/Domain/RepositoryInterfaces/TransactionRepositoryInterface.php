<?php

declare(strict_types=1);

namespace Modules\Transaction\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Transaction\Domain\Entities\Transaction;

interface TransactionRepositoryInterface extends RepositoryInterface
{
    public function save(Transaction $transaction): Transaction;

    public function findById(int $id): ?Transaction;

    public function findByReference(int $tenantId, string $referenceNumber): ?Transaction;

    public function findByReferenceEntity(int $tenantId, string $referenceType, int $referenceId): Collection;

    public function findByType(int $tenantId, string $transactionType): Collection;

    public function list(array $filters = [], int $perPage = 15, int $page = 1): mixed;
}
