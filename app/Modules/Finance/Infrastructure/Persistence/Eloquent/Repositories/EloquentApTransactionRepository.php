<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Finance\Domain\Entities\ApTransaction;
use Modules\Finance\Domain\RepositoryInterfaces\ApTransactionRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\ApTransactionModel;

class EloquentApTransactionRepository extends EloquentRepository implements ApTransactionRepositoryInterface
{
    public function __construct(ApTransactionModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (ApTransactionModel $m): ApTransaction => $this->mapToDomain($m));
    }

    public function save(ApTransaction $ap): ApTransaction
    {
        $data = [
            'tenant_id' => $ap->getTenantId(),
            'supplier_id' => $ap->getSupplierId(),
            'account_id' => $ap->getAccountId(),
            'transaction_type' => $ap->getTransactionType(),
            'reference_type' => $ap->getReferenceType(),
            'reference_id' => $ap->getReferenceId(),
            'amount' => $ap->getAmount(),
            'balance_after' => $ap->getBalanceAfter(),
            'transaction_date' => $ap->getTransactionDate()->format('Y-m-d'),
            'due_date' => $ap->getDueDate()?->format('Y-m-d'),
            'currency_id' => $ap->getCurrencyId(),
            'is_reconciled' => $ap->isReconciled(),
        ];

        $model = $ap->getId() ? $this->update($ap->getId(), $data) : $this->create($data);

        /** @var ApTransactionModel $model */
        return $this->toDomainEntity($model);
    }

    public function getSupplierBalance(int $tenantId, int $supplierId): string
    {
        $latest = ApTransactionModel::query()
            ->where('tenant_id', $tenantId)
            ->where('supplier_id', $supplierId)
            ->orderByDesc('id')
            ->first();

        return $latest !== null ? (string) $latest->balance_after : '0.000000';
    }

    private function mapToDomain(ApTransactionModel $m): ApTransaction
    {
        return new ApTransaction(
            tenantId: (int) $m->tenant_id,
            supplierId: (int) $m->supplier_id,
            accountId: (int) $m->account_id,
            transactionType: (string) $m->transaction_type,
            amount: (float) $m->amount,
            balanceAfter: (float) $m->balance_after,
            transactionDate: $m->transaction_date,
            currencyId: (int) $m->currency_id,
            referenceType: $m->reference_type,
            referenceId: $m->reference_id !== null ? (int) $m->reference_id : null,
            dueDate: $m->due_date,
            isReconciled: (bool) $m->is_reconciled,
            id: (int) $m->id,
            createdAt: $m->created_at,
            updatedAt: $m->updated_at,
        );
    }
}
