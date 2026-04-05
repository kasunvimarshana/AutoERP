<?php
declare(strict_types=1);
namespace Modules\HR\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HR\Domain\Entities\Position;

class PositionResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var Position $pos */
        $pos = $this->resource;
        return [
            'id'              => $pos->getId(),
            'tenant_id'       => $pos->getTenantId(),
            'department_id'   => $pos->getDepartmentId(),
            'title'           => $pos->getTitle(),
            'code'            => $pos->getCode(),
            'description'     => $pos->getDescription(),
            'employment_type' => $pos->getEmploymentType(),
            'min_salary'      => $pos->getMinSalary(),
            'max_salary'      => $pos->getMaxSalary(),
            'is_active'       => $pos->isActive(),
            'created_at'      => $pos->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at'      => $pos->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
