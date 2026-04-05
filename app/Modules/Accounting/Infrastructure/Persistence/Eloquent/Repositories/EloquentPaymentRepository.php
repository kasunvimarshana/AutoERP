<?php

declare(strict_types=1);

namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Accounting\Domain\Entities\Payment;
use Modules\Accounting\Domain\RepositoryInterfaces\PaymentRepositoryInterface;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Models\PaymentModel;

class EloquentPaymentRepository implements PaymentRepositoryInterface
{
    public function __construct(private readonly PaymentModel $model) {}

    private function toEntity(PaymentModel $m): Payment
    {
        return new Payment(
            $m->id, $m->tenant_id, $m->payable_type, $m->payable_id,
            (float) $m->amount, $m->currency, $m->payment_method, $m->status, $m->direction,
            $m->reference, $m->notes, $m->payment_date, $m->journal_entry_id,
            $m->created_at, $m->updated_at
        );
    }

    public function findById(int $id): ?Payment
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

    public function findByPayable(string $payableType, int $payableId): array
    {
        return $this->model->newQuery()
            ->where('payable_type', $payableType)->where('payable_id', $payableId)
            ->get()->map(fn($m) => $this->toEntity($m))->all();
    }

    public function create(array $data): Payment
    {
        $m = $this->model->newQuery()->create($data);
        return $this->toEntity($m);
    }

    public function update(int $id, array $data): ?Payment
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
