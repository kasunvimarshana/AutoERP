<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Finance\Domain\RepositoryInterfaces\JournalEntryRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\JournalEntryModel;

final class EloquentJournalEntryRepository extends EloquentRepository implements JournalEntryRepositoryInterface
{
    public function __construct(JournalEntryModel $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findWithLines(int $id): mixed
    {
        return $this->model->newQuery()
            ->with(['lines.account'])
            ->find($id);
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
    public function findByDateRange(int $tenantId, string $fromDate, string $toDate): Collection
    {
        return $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereBetween('entry_date', [$fromDate, $toDate])
            ->with(['lines'])
            ->orderBy('entry_date')
            ->orderBy('reference_number')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function findPostedByDateRange(int $tenantId, string $fromDate, string $toDate): Collection
    {
        return $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('status', 'posted')
            ->whereBetween('entry_date', [$fromDate, $toDate])
            ->with(['lines'])
            ->orderBy('entry_date')
            ->orderBy('reference_number')
            ->get();
    }

    /**
     * {@inheritdoc}
     *
     * Generates sequential reference numbers in format JE-YYYYMM-NNNNN.
     */
    public function nextReferenceNumber(int $tenantId): string
    {
        $prefix  = config('finance.journal_reference_prefix', 'JE');
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
