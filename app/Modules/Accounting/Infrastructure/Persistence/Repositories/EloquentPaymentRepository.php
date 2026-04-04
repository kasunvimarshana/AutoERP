<?php
namespace Modules\Accounting\Infrastructure\Persistence\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Accounting\Domain\Entities\Payment;
use Modules\Accounting\Domain\Repositories\PaymentRepositoryInterface;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Models\PaymentModel;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;

class EloquentPaymentRepository extends EloquentRepository implements PaymentRepositoryInterface
{
    public function __construct(PaymentModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?Payment
    {
        $model = $this->model->find($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findByReference(int $tenantId, string $ref): ?Payment
    {
        $model = $this->model->where('tenant_id', $tenantId)->where('reference_number', $ref)->first();
        return $model ? $this->toEntity($model) : null;
    }

    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->where('tenant_id', $tenantId);
        $this->applyFilters($query, $filters);
        return $query->paginate($perPage);
    }

    public function create(array $data): Payment
    {
        $model = $this->model->create($data);
        return $this->toEntity($model);
    }

    public function update(Payment $payment, array $data): Payment
    {
        $model = $this->model->findOrFail($payment->id);
        $model->fill($data)->save();
        return $this->toEntity($model);
    }

    public function delete(Payment $payment): bool
    {
        $model = $this->model->findOrFail($payment->id);
        return (bool) $model->delete();
    }

    private function toEntity(PaymentModel $model): Payment
    {
        return new Payment(
            id:              $model->id,
            tenantId:        $model->tenant_id,
            referenceNumber: $model->reference_number,
            status:          $model->status,
            method:          $model->method,
            amount:          (float) $model->amount,
            currency:        $model->currency,
            payableType:     $model->payable_type,
            payableId:       $model->payable_id,
            paidBy:          $model->paid_by,
            paidAt:          $model->paid_at?->toDateTimeString(),
            notes:           $model->notes,
            journalEntryId:  $model->journal_entry_id,
        );
    }
}
