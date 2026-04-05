<?php

declare(strict_types=1);

namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Accounting\Domain\Entities\Refund;
use Modules\Accounting\Domain\RepositoryInterfaces\RefundRepositoryInterface;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Models\RefundModel;

class EloquentRefundRepository implements RefundRepositoryInterface
{
    public function __construct(private readonly RefundModel $model) {}

    private function toEntity(RefundModel $m): Refund
    {
        return new Refund(
            $m->id, $m->tenant_id, $m->original_payment_id,
            (float) $m->amount, $m->currency, $m->status, $m->reason, $m->reference,
            $m->refund_date, $m->journal_entry_id, $m->created_at, $m->updated_at
        );
    }

    public function findById(int $id): ?Refund
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->model->newQuery()->where('tenant_id', $tenantId)
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(fn($m) => $this->toEntity($m));
    }

    public function findByPayment(int $paymentId): array
    {
        return $this->model->newQuery()->where('original_payment_id', $paymentId)
            ->get()->map(fn($m) => $this->toEntity($m))->all();
    }

    public function create(array $data): Refund
    {
        $m = $this->model->newQuery()->create($data);
        return $this->toEntity($m);
    }

    public function update(int $id, array $data): ?Refund
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) return null;
        $m->update($data);
        return $this->toEntity($m->fresh());
    }

    public function delete(int $id): bool
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? (bool) $m->delete() : false;
    }
}
