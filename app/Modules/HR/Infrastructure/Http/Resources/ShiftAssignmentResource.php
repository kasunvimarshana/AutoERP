<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HR\Domain\Entities\ShiftAssignment;

class ShiftAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var ShiftAssignment $entity */
        $entity = $this->resource;

        return [
            'id' => $entity->getId(),
            'tenant_id' => $entity->getTenantId(),
            'employee_id' => $entity->getEmployeeId(),
            'shift_id' => $entity->getShiftId(),
            'effective_from' => $entity->getEffectiveFrom()->format('Y-m-d'),
            'effective_to' => $entity->getEffectiveTo()?->format('Y-m-d'),
            'created_at' => $entity->getCreatedAt()->format('c'),
            'updated_at' => $entity->getUpdatedAt()->format('c'),
        ];
    }
}
