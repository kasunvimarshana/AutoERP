<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Service\Domain\Entities\ServiceReturn;
use Modules\Service\Domain\RepositoryInterfaces\ServiceReturnRepositoryInterface;
use Modules\Service\Infrastructure\Persistence\Eloquent\Models\ServiceReturnModel;

class EloquentServiceReturnRepository implements ServiceReturnRepositoryInterface
{
    public function __construct(private readonly ServiceReturnModel $model) {}

    public function findById(int $tenantId, int $id): ?ServiceReturn
    {
        /** @var ServiceReturnModel|null $model */
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();

        return $model !== null ? $this->mapModelToEntity($model) : null;
    }

    public function findByWorkOrder(int $tenantId, int $workOrderId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('service_work_order_id', $workOrderId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ServiceReturnModel $m) => $this->mapModelToEntity($m))
            ->all();
    }

    public function save(ServiceReturn $return): ServiceReturn
    {
        $payload = [
            'tenant_id' => $return->getTenantId(),
            'org_unit_id' => $return->getOrgUnitId(),
            'row_version' => $return->getRowVersion(),
            'service_work_order_id' => $return->getServiceWorkOrderId(),
            'return_number' => $return->getReturnNumber(),
            'return_type' => $return->getReturnType(),
            'status' => $return->getStatus(),
            'reason_code' => $return->getReasonCode(),
            'processed_by' => $return->getProcessedBy(),
            'processed_at' => $return->getProcessedAt(),
            'currency_id' => $return->getCurrencyId(),
            'total_amount' => $return->getTotalAmount(),
            'journal_entry_id' => $return->getJournalEntryId(),
            'payment_id' => $return->getPaymentId(),
            'notes' => $return->getNotes(),
            'metadata' => $return->getMetadata(),
        ];

        $id = $return->getId();

        if ($id !== null) {
            $this->model->newQuery()
                ->where('tenant_id', $return->getTenantId())
                ->where('id', $id)
                ->update($payload);

            /** @var ServiceReturnModel $saved */
            $saved = $this->model->newQuery()
                ->where('tenant_id', $return->getTenantId())
                ->where('id', $id)
                ->firstOrFail();
        } else {
            /** @var ServiceReturnModel $saved */
            $saved = $this->model->newQuery()->create($payload);
        }

        return $this->mapModelToEntity($saved);
    }

    public function delete(int $tenantId, int $id): bool
    {
        return (bool) $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->delete();
    }

    private function mapModelToEntity(ServiceReturnModel $model): ServiceReturn
    {
        return new ServiceReturn(
            tenantId: (int) $model->tenant_id,
            serviceWorkOrderId: (int) $model->service_work_order_id,
            returnNumber: (string) $model->return_number,
            returnType: (string) $model->return_type,
            status: (string) $model->status,
            orgUnitId: $model->org_unit_id !== null ? (int) $model->org_unit_id : null,
            reasonCode: $model->reason_code,
            processedBy: $model->processed_by !== null ? (int) $model->processed_by : null,
            processedAt: $model->processed_at !== null ? (string) $model->processed_at : null,
            currencyId: $model->currency_id !== null ? (int) $model->currency_id : null,
            totalAmount: (float) $model->total_amount,
            journalEntryId: $model->journal_entry_id !== null ? (int) $model->journal_entry_id : null,
            paymentId: $model->payment_id !== null ? (int) $model->payment_id : null,
            notes: $model->notes,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            rowVersion: (int) $model->row_version,
            createdAt: $model->created_at !== null ? new \DateTimeImmutable((string) $model->created_at) : null,
            updatedAt: $model->updated_at !== null ? new \DateTimeImmutable((string) $model->updated_at) : null,
            id: (int) $model->id,
        );
    }
}
