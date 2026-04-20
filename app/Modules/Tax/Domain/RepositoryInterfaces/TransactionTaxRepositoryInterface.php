<?php

declare(strict_types=1);

namespace Modules\Tax\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Tax\Domain\Entities\TransactionTax;

interface TransactionTaxRepositoryInterface extends RepositoryInterface
{
    /**
     * @param  list<array<string, mixed>>  $taxLines
     * @return list<TransactionTax>
     */
    public function saveManyForReference(int $tenantId, string $referenceType, int $referenceId, array $taxLines): array;

    public function deleteByReference(int $tenantId, string $referenceType, int $referenceId): void;

    /**
     * @return list<TransactionTax>
     */
    public function listByReference(int $tenantId, string $referenceType, int $referenceId): array;

    public function find(int|string $id, array $columns = ['*']): ?TransactionTax;
}
