<?php

namespace Modules\Returns\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Returns\Domain\Entities\CreditMemo;
use Modules\Returns\Domain\RepositoryInterfaces\CreditMemoRepositoryInterface;
use Modules\Returns\Infrastructure\Persistence\Eloquent\Models\CreditMemoModel;

class EloquentCreditMemoRepository extends EloquentRepository implements CreditMemoRepositoryInterface
{
    public function __construct(CreditMemoModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?CreditMemo
    {
        $model = parent::findById($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function findByMemoNumber(int $tenantId, string $memoNumber): ?CreditMemo
    {
        $model = $this->model->where('tenant_id', $tenantId)->where('memo_number', $memoNumber)->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->where('tenant_id', $tenantId);
        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    public function create(array $data): CreditMemo
    {
        $model = parent::create($data);

        return $this->toEntity($model);
    }

    public function update(CreditMemo $memo, array $data): CreditMemo
    {
        $model = $this->model->findOrFail($memo->id);
        $updated = parent::update($model, $data);

        return $this->toEntity($updated);
    }

    public function save(CreditMemo $memo): CreditMemo
    {
        $model = $this->model->findOrFail($memo->id);
        $updated = parent::update($model, [
            'status'    => $memo->status,
            'issued_at' => $memo->issuedAt,
            'issued_by' => $memo->issuedBy,
            'notes'     => $memo->notes,
        ]);

        return $this->toEntity($updated);
    }

    private function toEntity(object $model): CreditMemo
    {
        return new CreditMemo(
            id: $model->id,
            tenantId: $model->tenant_id,
            memoNumber: $model->memo_number,
            stockReturnId: $model->stock_return_id,
            amount: (float) $model->amount,
            status: $model->status,
            customerId: $model->customer_id,
            currency: $model->currency,
            notes: $model->notes,
            issuedAt: $model->issued_at ? new \DateTimeImmutable((string) $model->issued_at) : null,
            issuedBy: $model->issued_by,
        );
    }
}
