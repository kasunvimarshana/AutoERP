<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Service\Domain\Entities\ServiceWorkOrder;
use Modules\Service\Domain\RepositoryInterfaces\ServiceWorkOrderRepositoryInterface;
use Modules\Service\Infrastructure\Persistence\Eloquent\Models\ServiceWorkOrderModel;

class EloquentServiceWorkOrderRepository extends EloquentRepository implements ServiceWorkOrderRepositoryInterface
{
    public function __construct(private readonly ServiceWorkOrderModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(
            fn (ServiceWorkOrderModel $m): ServiceWorkOrder => $this->mapModelToEntity($m),
        );
    }

    public function findById(int $tenantId, int $id): ?ServiceWorkOrder
    {
        /** @var ServiceWorkOrderModel|null $model */
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();

        return $model !== null ? $this->mapModelToEntity($model) : null;
    }

    public function findByTenant(int $tenantId, ?int $orgUnitId = null, array $filters = []): array
    {
        $query = $this->model->newQuery()
            ->where('tenant_id', $tenantId);

        if ($orgUnitId !== null) {
            $query->where('org_unit_id', $orgUnitId);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['asset_id'])) {
            $query->where('asset_id', (int) $filters['asset_id']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        return $query->orderByDesc('opened_at')
            ->get()
            ->map(fn (ServiceWorkOrderModel $m) => $this->mapModelToEntity($m))
            ->all();
    }

    public function save(ServiceWorkOrder $workOrder): ServiceWorkOrder
    {
        $payload = [
            'tenant_id' => $workOrder->getTenantId(),
            'org_unit_id' => $workOrder->getOrgUnitId(),
            'row_version' => $workOrder->getRowVersion(),
            'job_card_number' => $workOrder->getJobCardNumber(),
            'asset_id' => $workOrder->getAssetId(),
            'customer_id' => $workOrder->getCustomerId(),
            'opened_by' => $workOrder->getOpenedBy(),
            'assigned_team_org_unit_id' => $workOrder->getAssignedTeamOrgUnitId(),
            'service_type' => $workOrder->getServiceType(),
            'priority' => $workOrder->getPriority(),
            'status' => $workOrder->getStatus(),
            'opened_at' => $workOrder->getOpenedAt(),
            'scheduled_start_at' => $workOrder->getScheduledStartAt(),
            'scheduled_end_at' => $workOrder->getScheduledEndAt(),
            'started_at' => $workOrder->getStartedAt(),
            'completed_at' => $workOrder->getCompletedAt(),
            'meter_in' => $workOrder->getMeterIn(),
            'meter_out' => $workOrder->getMeterOut(),
            'meter_unit' => $workOrder->getMeterUnit(),
            'symptoms' => $workOrder->getSymptoms(),
            'diagnosis' => $workOrder->getDiagnosis(),
            'resolution' => $workOrder->getResolution(),
            'billing_mode' => $workOrder->getBillingMode(),
            'currency_id' => $workOrder->getCurrencyId(),
            'labor_subtotal' => $workOrder->getLaborSubtotal(),
            'parts_subtotal' => $workOrder->getPartsSubtotal(),
            'other_subtotal' => $workOrder->getOtherSubtotal(),
            'tax_total' => $workOrder->getTaxTotal(),
            'grand_total' => $workOrder->getGrandTotal(),
            'journal_entry_id' => $workOrder->getJournalEntryId(),
            'notes' => $workOrder->getNotes(),
            'metadata' => $workOrder->getMetadata(),
        ];

        $id = $workOrder->getId();

        if ($id !== null) {
            $this->model->newQuery()
                ->where('tenant_id', $workOrder->getTenantId())
                ->where('id', $id)
                ->update($payload);

            /** @var ServiceWorkOrderModel $saved */
            $saved = $this->model->newQuery()
                ->where('tenant_id', $workOrder->getTenantId())
                ->where('id', $id)
                ->firstOrFail();
        } else {
            /** @var ServiceWorkOrderModel $saved */
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

    public function nextJobCardNumber(int $tenantId, ?int $orgUnitId): string
    {
        $count = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->withTrashed()
            ->count();

        $seq = str_pad((string) ($count + 1), 6, '0', STR_PAD_LEFT);
        $prefix = 'WO-'.date('Ym');

        return "{$prefix}-{$seq}";
    }

    private function mapModelToEntity(ServiceWorkOrderModel $model): ServiceWorkOrder
    {
        return new ServiceWorkOrder(
            tenantId: (int) $model->tenant_id,
            assetId: (int) $model->asset_id,
            serviceType: (string) $model->service_type,
            currencyId: (int) $model->currency_id,
            billingMode: (string) $model->billing_mode,
            status: (string) $model->status,
            orgUnitId: $model->org_unit_id !== null ? (int) $model->org_unit_id : null,
            jobCardNumber: (string) $model->job_card_number,
            customerId: $model->customer_id !== null ? (int) $model->customer_id : null,
            openedBy: $model->opened_by !== null ? (int) $model->opened_by : null,
            assignedTeamOrgUnitId: $model->assigned_team_org_unit_id !== null ? (int) $model->assigned_team_org_unit_id : null,
            priority: (string) $model->priority,
            openedAt: $model->opened_at !== null ? (string) $model->opened_at : null,
            scheduledStartAt: $model->scheduled_start_at !== null ? (string) $model->scheduled_start_at : null,
            scheduledEndAt: $model->scheduled_end_at !== null ? (string) $model->scheduled_end_at : null,
            startedAt: $model->started_at !== null ? (string) $model->started_at : null,
            completedAt: $model->completed_at !== null ? (string) $model->completed_at : null,
            meterIn: $model->meter_in !== null ? (float) $model->meter_in : null,
            meterOut: $model->meter_out !== null ? (float) $model->meter_out : null,
            meterUnit: (string) $model->meter_unit,
            symptoms: $model->symptoms,
            diagnosis: $model->diagnosis,
            resolution: $model->resolution,
            laborSubtotal: (float) $model->labor_subtotal,
            partsSubtotal: (float) $model->parts_subtotal,
            otherSubtotal: (float) $model->other_subtotal,
            taxTotal: (float) $model->tax_total,
            grandTotal: (float) $model->grand_total,
            journalEntryId: $model->journal_entry_id !== null ? (int) $model->journal_entry_id : null,
            notes: $model->notes,
            metadata: $model->metadata,
            rowVersion: (int) $model->row_version,
            id: (int) $model->id,
            createdAt: $model->created_at ? new \DateTimeImmutable($model->created_at->toISOString()) : null,
            updatedAt: $model->updated_at ? new \DateTimeImmutable($model->updated_at->toISOString()) : null,
        );
    }
}
