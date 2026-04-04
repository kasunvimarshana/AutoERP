<?php
namespace Modules\Configuration\Infrastructure\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Configuration\Domain\Entities\OrganizationUnit;

class OrganizationUnitResource extends JsonResource
{
    public function __construct(private readonly OrganizationUnit $unit) { parent::__construct($unit); }
    public function toArray($request): array {
        return [
            'id' => $this->unit->id,
            'tenant_id' => $this->unit->tenantId,
            'name' => $this->unit->name,
            'code' => $this->unit->code,
            'type' => $this->unit->type,
            'parent_id' => $this->unit->parentId,
            'address' => $this->unit->address,
            'is_active' => $this->unit->isActive,
        ];
    }
}
