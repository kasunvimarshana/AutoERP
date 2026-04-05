<?php
declare(strict_types=1);
namespace Modules\Configuration\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Configuration\Domain\Entities\OrgUnit;

class OrgUnitResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var OrgUnit $unit */
        $unit = $this->resource;
        return [
            'id' => $unit->getId(),
            'tenant_id' => $unit->getTenantId(),
            'parent_id' => $unit->getParentId(),
            'name' => $unit->getName(),
            'code' => $unit->getCode(),
            'type' => $unit->getType(),
            'level' => $unit->getLevel(),
            'is_active' => $unit->isActive(),
            'created_at' => $unit->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at' => $unit->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
