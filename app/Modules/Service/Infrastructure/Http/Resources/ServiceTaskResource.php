<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Service\Domain\Entities\ServiceTask;

class ServiceTaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var ServiceTask $task */
        $task = $this->resource;

        return [
            'id' => $task->getId(),
            'tenant_id' => $task->getTenantId(),
            'org_unit_id' => $task->getOrgUnitId(),
            'service_work_order_id' => $task->getServiceWorkOrderId(),
            'line_number' => $task->getLineNumber(),
            'task_code' => $task->getTaskCode(),
            'description' => $task->getDescription(),
            'status' => $task->getStatus(),
            'assigned_employee_id' => $task->getAssignedEmployeeId(),
            'estimated_hours' => $task->getEstimatedHours(),
            'actual_hours' => $task->getActualHours(),
            'labor_rate' => $task->getLaborRate(),
            'labor_amount' => $task->getLaborAmount(),
            'commission_amount' => $task->getCommissionAmount(),
            'incentive_amount' => $task->getIncentiveAmount(),
            'completed_at' => $task->getCompletedAt(),
            'metadata' => $task->getMetadata(),
            'row_version' => $task->getRowVersion(),
            'created_at' => $task->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updated_at' => $task->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
