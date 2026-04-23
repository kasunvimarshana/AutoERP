<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HR\Domain\Entities\LeaveType;

class LeaveTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var LeaveType $entity */
        $entity = $this->resource;

        return [
            'id' => $entity->getId(),
            'tenant_id' => $entity->getTenantId(),
            'name' => $entity->getName(),
            'code' => $entity->getCode(),
            'description' => $entity->getDescription(),
            'max_days_per_year' => $entity->getMaxDaysPerYear(),
            'carry_forward_days' => $entity->getCarryForwardDays(),
            'is_paid' => $entity->isPaid(),
            'requires_approval' => $entity->requiresApproval(),
            'applicable_gender' => $entity->getApplicableGender(),
            'min_service_days' => $entity->getMinServiceDays(),
            'is_active' => $entity->isActive(),
            'metadata' => $entity->getMetadata(),
            'created_at' => $entity->getCreatedAt()->format('c'),
            'updated_at' => $entity->getUpdatedAt()->format('c'),
        ];
    }
}
