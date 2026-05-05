<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceJobCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'org_unit_id' => $this->getOrgUnitId(),
            'job_number' => $this->getJobNumber(),
            'asset_id' => $this->getAssetId(),
            'customer_id' => $this->getCustomerId(),
            'maintenance_plan_id' => $this->getMaintenancePlanId(),
            'service_type' => $this->getServiceType(),
            'priority' => $this->getPriority(),
            'status' => $this->getStatus(),
            'scheduled_at' => $this->getScheduledAt()?->format('c'),
            'started_at' => $this->getStartedAt()?->format('c'),
            'completed_at' => $this->getCompletedAt()?->format('c'),
            'odometer_in' => $this->getOdometerIn(),
            'odometer_out' => $this->getOdometerOut(),
            'is_billable' => $this->isBillable(),
            'parts_subtotal' => $this->getPartsSubtotal(),
            'labour_subtotal' => $this->getLabourSubtotal(),
            'discount_amount' => $this->getDiscountAmount(),
            'tax_amount' => $this->getTaxAmount(),
            'total_amount' => $this->getTotalAmount(),
            'assigned_to' => $this->getAssignedTo(),
            'ar_transaction_id' => $this->getArTransactionId(),
            'journal_entry_id' => $this->getJournalEntryId(),
            'diagnosis' => $this->getDiagnosis(),
            'work_performed' => $this->getWorkPerformed(),
            'notes' => $this->getNotes(),
            'metadata' => $this->getMetadata(),
            'row_version' => $this->getRowVersion(),
            'created_at' => $this->getCreatedAt()->format('c'),
            'updated_at' => $this->getUpdatedAt()->format('c'),
        ];
    }
}
