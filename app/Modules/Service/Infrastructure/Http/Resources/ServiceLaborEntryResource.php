<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Service\Domain\Entities\ServiceLaborEntry;

class ServiceLaborEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var ServiceLaborEntry $entry */
        $entry = $this->resource;

        return [
            'id' => $entry->getId(),
            'tenant_id' => $entry->getTenantId(),
            'org_unit_id' => $entry->getOrgUnitId(),
            'service_work_order_id' => $entry->getServiceWorkOrderId(),
            'service_task_id' => $entry->getServiceTaskId(),
            'employee_id' => $entry->getEmployeeId(),
            'started_at' => $entry->getStartedAt(),
            'ended_at' => $entry->getEndedAt(),
            'hours_worked' => $entry->getHoursWorked(),
            'labor_rate' => $entry->getLaborRate(),
            'labor_amount' => $entry->getLaborAmount(),
            'commission_rate' => $entry->getCommissionRate(),
            'commission_amount' => $entry->getCommissionAmount(),
            'incentive_amount' => $entry->getIncentiveAmount(),
            'status' => $entry->getStatus(),
            'metadata' => $entry->getMetadata(),
            'row_version' => $entry->getRowVersion(),
            'created_at' => $entry->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updated_at' => $entry->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
