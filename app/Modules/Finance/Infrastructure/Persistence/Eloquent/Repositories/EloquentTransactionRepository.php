<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Finance\Domain\RepositoryInterfaces\TransactionRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\TransactionModel;

final class EloquentTransactionRepository extends EloquentRepository implements TransactionRepositoryInterface
{
    public function __construct(TransactionModel $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findByReference(string $referenceNumber, int $tenantId): mixed
    {
        return $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('reference_number', $referenceNumber)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function findByAccount(int $accountId, int $tenantId, ?string $fromDate = null, ?string $toDate = null): Collection
    {
        $query = $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where(static function ($q) use ($accountId) {
                $q->where('from_account_id', $accountId)
                  ->orWhere('to_account_id', $accountId);
            });

        if ($fromDate) {
            $query->whereDate('transaction_date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('transaction_date', '<=', $toDate);
        }

        return $query->orderBy('transaction_date')->get();
    }

    /**
     * {@inheritdoc}
     */
    public function findByType(string $type, int $tenantId): Collection
    {
        return $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('type', $type)
            ->where('tenant_id', $tenantId)
            ->orderBy('transaction_date', 'desc')
            ->get();
    }

    /**
     * {@inheritdoc}
     *
     * Generates sequential reference numbers in format TXN-YYYYMM-NNNNN.
     */
    public function nextReferenceNumber(int $tenantId): string
    {
        $prefix    = config('finance.transaction_reference_prefix', 'TXN');
        $yearMonth = now()->format('Ym');

        $pattern = "{$prefix}-{$yearMonth}-%";

        $last = $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('reference_number', 'like', $pattern)
            ->withTrashed()
            ->orderByDesc('reference_number')
            ->lockForUpdate()
            ->value('reference_number');

        if ($last) {
            $sequence = (int) substr($last, -5) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s-%s-%05d', $prefix, $yearMonth, $sequence);
    }
}
