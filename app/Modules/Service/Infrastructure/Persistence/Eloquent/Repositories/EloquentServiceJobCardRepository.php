<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Service\Domain\Entities\ServiceJobCard;
use Modules\Service\Domain\RepositoryInterfaces\ServiceJobCardRepositoryInterface;
use Modules\Service\Infrastructure\Persistence\Eloquent\Models\ServiceJobCardModel;

class EloquentServiceJobCardRepository implements ServiceJobCardRepositoryInterface
{
    public function __construct(
        private readonly ServiceJobCardModel $model,
    ) {}

    public function save(ServiceJobCard $jobCard): ServiceJobCard
    {
        if ($jobCard->getId() !== null) {
            /** @var ServiceJobCardModel $record */
            $record = $this->model->newQuery()->findOrFail($jobCard->getId());
            $record->update($this->toArray($jobCard));
            $record->refresh();
        } else {
            /** @var ServiceJobCardModel $record */
            $record = $this->model->newQuery()->create($this->toArray($jobCard));
        }

        return $this->mapToEntity($record);
    }

    public function findById(int $tenantId, int $id): ?ServiceJobCard
    {
        /** @var ServiceJobCardModel|null $record */
        $record = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $record !== null ? $this->mapToEntity($record) : null;
    }

    public function findByJobNumber(int $tenantId, string $jobNumber): ?ServiceJobCard
    {
        /** @var ServiceJobCardModel|null $record */
        $record = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('job_number', $jobNumber)
            ->first();

        return $record !== null ? $this->mapToEntity($record) : null;
    }

    public function paginate(int $tenantId, array $filters, int $perPage, int $page): array
    {
        $query = $this->model->newQuery()->where('tenant_id', $tenantId);

        foreach ($filters as $field => $value) {
            if ($value !== null && $value !== '') {
                $query->where($field, $value);
            }
        }

        $paginator = $query->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => array_map(
                fn (ServiceJobCardModel $m): ServiceJobCard => $this->mapToEntity($m),
                $paginator->items()
            ),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
        ];
    }

    public function existsByJobNumber(int $tenantId, string $jobNumber): bool
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('job_number', $jobNumber)
            ->exists();
    }

    private function toArray(ServiceJobCard $jobCard): array
    {
        return [
            'tenant_id' => $jobCard->getTenantId(),
            'org_unit_id' => $jobCard->getOrgUnitId(),
            'row_version' => $jobCard->getRowVersion(),
            'job_number' => $jobCard->getJobNumber(),
            'asset_id' => $jobCard->getAssetId(),
            'customer_id' => $jobCard->getCustomerId(),
            'maintenance_plan_id' => $jobCard->getMaintenancePlanId(),
            'service_type' => $jobCard->getServiceType(),
            'priority' => $jobCard->getPriority(),
            'status' => $jobCard->getStatus(),
            'scheduled_at' => $jobCard->getScheduledAt(),
            'started_at' => $jobCard->getStartedAt(),
            'completed_at' => $jobCard->getCompletedAt(),
            'odometer_in' => $jobCard->getOdometerIn(),
            'odometer_out' => $jobCard->getOdometerOut(),
            'is_billable' => $jobCard->isBillable(),
            'parts_subtotal' => $jobCard->getPartsSubtotal(),
            'labour_subtotal' => $jobCard->getLabourSubtotal(),
            'discount_amount' => $jobCard->getDiscountAmount(),
            'tax_amount' => $jobCard->getTaxAmount(),
            'total_amount' => $jobCard->getTotalAmount(),
            'assigned_to' => $jobCard->getAssignedTo(),
            'ar_transaction_id' => $jobCard->getArTransactionId(),
            'journal_entry_id' => $jobCard->getJournalEntryId(),
            'diagnosis' => $jobCard->getDiagnosis(),
            'work_performed' => $jobCard->getWorkPerformed(),
            'notes' => $jobCard->getNotes(),
            'metadata' => $jobCard->getMetadata(),
        ];
    }

    private function mapToEntity(ServiceJobCardModel $model): ServiceJobCard
    {
        return new ServiceJobCard(
            tenantId: (int) $model->tenant_id,
            jobNumber: (string) $model->job_number,
            serviceType: (string) $model->service_type,
            priority: (string) $model->priority,
            status: (string) $model->status,
            orgUnitId: $model->org_unit_id !== null ? (int) $model->org_unit_id : null,
            assetId: $model->asset_id !== null ? (int) $model->asset_id : null,
            customerId: $model->customer_id !== null ? (int) $model->customer_id : null,
            maintenancePlanId: $model->maintenance_plan_id !== null ? (int) $model->maintenance_plan_id : null,
            assignedTo: $model->assigned_to !== null ? (int) $model->assigned_to : null,
            arTransactionId: $model->ar_transaction_id !== null ? (int) $model->ar_transaction_id : null,
            journalEntryId: $model->journal_entry_id !== null ? (int) $model->journal_entry_id : null,
            scheduledAt: $model->scheduled_at,
            startedAt: $model->started_at,
            completedAt: $model->completed_at,
            odometerIn: $model->odometer_in !== null ? (string) $model->odometer_in : null,
            odometerOut: $model->odometer_out !== null ? (string) $model->odometer_out : null,
            isBillable: (bool) $model->is_billable,
            partsSubtotal: (string) $model->parts_subtotal,
            labourSubtotal: (string) $model->labour_subtotal,
            discountAmount: (string) $model->discount_amount,
            taxAmount: (string) $model->tax_amount,
            totalAmount: (string) $model->total_amount,
            diagnosis: $model->diagnosis,
            workPerformed: $model->work_performed,
            notes: $model->notes,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            rowVersion: (int) $model->row_version,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
            id: (int) $model->id,
        );
    }
}
