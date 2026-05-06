<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HR\Domain\Entities\LeavePolicy;

class LeavePolicyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var LeavePolicy $entity */
        $entity = $this->resource;

        return [
            'id' => $entity->getId(),
            'tenant_id' => $entity->getTenantId(),
            'leave_type_id' => $entity->getLeaveTypeId(),
            'name' => $entity->getName(),
            'accrual_type' => $entity->getAccrualType(),
            'accrual_amount' => $entity->getAccrualAmount(),
            'org_unit_id' => $entity->getOrgUnitId(),
            'is_active' => $entity->isActive(),
            'metadata' => $entity->getMetadata(),
            'created_at' => $entity->getCreatedAt()->format('c'),
            'updated_at' => $entity->getUpdatedAt()->format('c'),
        ];
    }
}
