<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Finance\Domain\Entities\ArTransaction;
use Modules\Finance\Domain\RepositoryInterfaces\ArTransactionRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\ArTransactionModel;

class EloquentArTransactionRepository extends EloquentRepository implements ArTransactionRepositoryInterface
{
    public function __construct(ArTransactionModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (ArTransactionModel $m): ArTransaction => $this->mapToDomain($m));
    }

    public function save(ArTransaction $ar): ArTransaction
    {
        $data = [
            'tenant_id' => $ar->getTenantId(),
            'customer_id' => $ar->getCustomerId(),
            'account_id' => $ar->getAccountId(),
            'transaction_type' => $ar->getTransactionType(),
            'reference_type' => $ar->getReferenceType(),
            'reference_id' => $ar->getReferenceId(),
            'amount' => $ar->getAmount(),
            'balance_after' => $ar->getBalanceAfter(),
            'transaction_date' => $ar->getTransactionDate()->format('Y-m-d'),
            'due_date' => $ar->getDueDate()?->format('Y-m-d'),
            'currency_id' => $ar->getCurrencyId(),
            'is_reconciled' => $ar->isReconciled(),
            'row_version' => $ar->getRowVersion(),
        ];

        $model = $ar->getId() ? $this->update($ar->getId(), $data) : $this->create($data);

        /** @var ArTransactionModel $model */
        return $this->toDomainEntity($model);
    }

    public function getCustomerBalance(int $tenantId, int $customerId): string
    {
        $latest = ArTransactionModel::query()
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customerId)
            ->orderByDesc('id')
            ->first();

        return $latest !== null ? (string) $latest->balance_after : '0.000000';
    }

    private function mapToDomain(ArTransactionModel $m): ArTransaction
    {
        return new ArTransaction(
            tenantId: (int) $m->tenant_id,
            customerId: (int) $m->customer_id,
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
            rowVersion: (int) ($m->row_version ?? 1),
            id: (int) $m->id,
            createdAt: $m->created_at,
            updatedAt: $m->updated_at,
        );
    }
}
