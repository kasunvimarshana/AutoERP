<?php
namespace Modules\Authorization\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Authorization\Domain\Entities\Role;

class RoleResource extends JsonResource
{
    public function __construct(private readonly Role $role) { parent::__construct($role); }

    public function toArray($request): array
    {
        return [
            'id'          => $this->role->id,
            'tenant_id'   => $this->role->tenantId,
            'name'        => $this->role->name,
            'guard_name'  => $this->role->guardName,
            'description' => $this->role->description,
        ];
    }
}
