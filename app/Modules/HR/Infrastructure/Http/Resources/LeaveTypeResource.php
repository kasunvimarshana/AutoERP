<?php
declare(strict_types=1);
namespace Modules\HR\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HR\Domain\Entities\LeaveType;

class LeaveTypeResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var LeaveType $type */
        $type = $this->resource;
        return [
            'id'                => $type->getId(),
            'tenant_id'         => $type->getTenantId(),
            'name'              => $type->getName(),
            'code'              => $type->getCode(),
            'description'       => $type->getDescription(),
            'default_days'      => $type->getDefaultDays(),
            'is_paid'           => $type->isPaid(),
            'requires_approval' => $type->requiresApproval(),
            'is_active'         => $type->isActive(),
            'created_at'        => $type->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at'        => $type->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
