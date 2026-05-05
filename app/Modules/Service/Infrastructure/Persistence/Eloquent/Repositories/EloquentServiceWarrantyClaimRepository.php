<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Service\Domain\Entities\ServiceWarrantyClaim;
use Modules\Service\Domain\RepositoryInterfaces\ServiceWarrantyClaimRepositoryInterface;
use Modules\Service\Infrastructure\Persistence\Eloquent\Models\ServiceWarrantyClaimModel;

class EloquentServiceWarrantyClaimRepository implements ServiceWarrantyClaimRepositoryInterface
{
    public function __construct(private readonly ServiceWarrantyClaimModel $model) {}

    public function findById(int $tenantId, int $id): ?ServiceWarrantyClaim
    {
        /** @var ServiceWarrantyClaimModel|null $model */
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
            ->map(fn (ServiceWarrantyClaimModel $m) => $this->mapModelToEntity($m))
            ->all();
    }

    public function save(ServiceWarrantyClaim $claim): ServiceWarrantyClaim
    {
        $payload = [
            'tenant_id' => $claim->getTenantId(),
            'org_unit_id' => $claim->getOrgUnitId(),
            'row_version' => $claim->getRowVersion(),
            'service_work_order_id' => $claim->getServiceWorkOrderId(),
            'supplier_id' => $claim->getSupplierId(),
            'warranty_provider' => $claim->getWarrantyProvider(),
            'claim_number' => $claim->getClaimNumber(),
            'status' => $claim->getStatus(),
            'currency_id' => $claim->getCurrencyId(),
            'claim_amount' => $claim->getClaimAmount(),
            'approved_amount' => $claim->getApprovedAmount(),
            'received_amount' => $claim->getReceivedAmount(),
            'submitted_at' => $claim->getSubmittedAt(),
            'resolved_at' => $claim->getResolvedAt(),
            'journal_entry_id' => $claim->getJournalEntryId(),
            'notes' => $claim->getNotes(),
            'metadata' => $claim->getMetadata(),
        ];

        $id = $claim->getId();

        if ($id !== null) {
            $this->model->newQuery()
                ->where('tenant_id', $claim->getTenantId())
                ->where('id', $id)
                ->update($payload);

            /** @var ServiceWarrantyClaimModel $saved */
            $saved = $this->model->newQuery()
                ->where('tenant_id', $claim->getTenantId())
                ->where('id', $id)
                ->firstOrFail();
        } else {
            /** @var ServiceWarrantyClaimModel $saved */
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

    private function mapModelToEntity(ServiceWarrantyClaimModel $model): ServiceWarrantyClaim
    {
        return new ServiceWarrantyClaim(
            tenantId: (int) $model->tenant_id,
            serviceWorkOrderId: (int) $model->service_work_order_id,
            warrantyProvider: (string) $model->warranty_provider,
            orgUnitId: $model->org_unit_id !== null ? (int) $model->org_unit_id : null,
            supplierId: $model->supplier_id !== null ? (int) $model->supplier_id : null,
            claimNumber: $model->claim_number,
            status: (string) $model->status,
            currencyId: $model->currency_id !== null ? (int) $model->currency_id : null,
            claimAmount: (float) $model->claim_amount,
            approvedAmount: (float) $model->approved_amount,
            receivedAmount: (float) $model->received_amount,
            submittedAt: $model->submitted_at !== null ? (string) $model->submitted_at : null,
            resolvedAt: $model->resolved_at !== null ? (string) $model->resolved_at : null,
            journalEntryId: $model->journal_entry_id !== null ? (int) $model->journal_entry_id : null,
            notes: $model->notes,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            rowVersion: (int) $model->row_version,
            createdAt: $model->created_at !== null ? new \DateTimeImmutable((string) $model->created_at) : null,
            updatedAt: $model->updated_at !== null ? new \DateTimeImmutable((string) $model->updated_at) : null,
            id: (int) $model->id,
        );
    }
}
