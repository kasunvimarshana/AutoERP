<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceMaintenancePlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'org_unit_id' => $this->getOrgUnitId(),
            'plan_code' => $this->getPlanCode(),
            'plan_name' => $this->getPlanName(),
            'description' => $this->getDescription(),
            'asset_id' => $this->getAssetId(),
            'product_id' => $this->getProductId(),
            'trigger_type' => $this->getTriggerType(),
            'interval_days' => $this->getIntervalDays(),
            'interval_km' => $this->getIntervalKm(),
            'interval_hours' => $this->getIntervalHours(),
            'advance_notice_days' => $this->getAdvanceNoticeDays(),
            'last_serviced_at' => $this->getLastServicedAt()?->format('c'),
            'next_service_due_at' => $this->getNextServiceDueAt()?->format('c'),
            'last_service_odometer' => $this->getLastServiceOdometer(),
            'next_service_odometer' => $this->getNextServiceOdometer(),
            'assigned_employee_id' => $this->getAssignedEmployeeId(),
            'status' => $this->getStatus(),
            'metadata' => $this->getMetadata(),
            'row_version' => $this->getRowVersion(),
            'created_at' => $this->getCreatedAt()->format('c'),
            'updated_at' => $this->getUpdatedAt()->format('c'),
        ];
    }
}
