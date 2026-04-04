<?php
namespace Modules\Accounting\Infrastructure\Persistence\Repositories;

use Modules\Accounting\Domain\Entities\Refund;
use Modules\Accounting\Domain\Repositories\RefundRepositoryInterface;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Models\RefundModel;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;

class EloquentRefundRepository extends EloquentRepository implements RefundRepositoryInterface
{
    public function __construct(RefundModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?Refund
    {
        $model = $this->model->find($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findByPayment(int $paymentId): array
    {
        return $this->model->where('payment_id', $paymentId)
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function create(array $data): Refund
    {
        $model = $this->model->create($data);
        return $this->toEntity($model);
    }

    public function update(Refund $refund, array $data): Refund
    {
        $model = $this->model->findOrFail($refund->id);
        $model->fill($data)->save();
        return $this->toEntity($model);
    }

    public function delete(Refund $refund): bool
    {
        $model = $this->model->findOrFail($refund->id);
        return (bool) $model->delete();
    }

    private function toEntity(RefundModel $model): Refund
    {
        return new Refund(
            id:             $model->id,
            tenantId:       $model->tenant_id,
            paymentId:      $model->payment_id,
            amount:         (float) $model->amount,
            currency:       $model->currency,
            status:         $model->status,
            reason:         $model->reason,
            processedBy:    $model->processed_by,
            processedAt:    $model->processed_at?->toDateTimeString(),
            journalEntryId: $model->journal_entry_id,
        );
    }
}
