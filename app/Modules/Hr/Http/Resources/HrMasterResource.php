<?php
declare(strict_types=1);
namespace Modules\Hr\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
abstract class HrMasterResource extends JsonResource
{
    public function toArray(Request $request): array { return ['id' => $this->getKey(), 'tenant_id' => $this->tenant_id, 'organization_unit_id' => $this->organization_unit_id, 'code' => $this->code, 'name' => $this->name, 'description' => $this->description, 'is_active' => (bool) $this->is_active, 'sort_order' => $this->sort_order ?? null]; }
}
