<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HR\Domain\Entities\LeaveBalance;

class LeaveBalanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var LeaveBalance $entity */
        $entity = $this->resource;

        return [
            'id' => $entity->getId(),
            'tenant_id' => $entity->getTenantId(),
            'employee_id' => $entity->getEmployeeId(),
            'leave_type_id' => $entity->getLeaveTypeId(),
            'year' => $entity->getYear(),
            'allocated' => $entity->getAllocated(),
            'used' => $entity->getUsed(),
            'pending' => $entity->getPending(),
            'carried' => $entity->getCarried(),
            'available' => $entity->getAvailable(),
            'created_at' => $entity->getCreatedAt()->format('c'),
            'updated_at' => $entity->getUpdatedAt()->format('c'),
        ];
    }
}
