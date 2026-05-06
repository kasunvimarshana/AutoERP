<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Service\Domain\Entities\ServiceWorkOrder;

class ServiceWorkOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var ServiceWorkOrder $workOrder */
        $workOrder = $this->resource;

        return [
            'id' => $workOrder->getId(),
            'tenant_id' => $workOrder->getTenantId(),
            'org_unit_id' => $workOrder->getOrgUnitId(),
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
            'notes' => $workOrder->getNotes(),
            'metadata' => $workOrder->getMetadata(),
            'row_version' => $workOrder->getRowVersion(),
            'created_at' => $workOrder->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updated_at' => $workOrder->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
