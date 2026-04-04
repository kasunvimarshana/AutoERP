<?php
namespace Modules\Authorization\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Authorization\Domain\Entities\Permission;

class PermissionResource extends JsonResource
{
    public function __construct(private readonly Permission $permission) { parent::__construct($permission); }

    public function toArray($request): array
    {
        return [
            'id'          => $this->permission->id,
            'name'        => $this->permission->name,
            'guard_name'  => $this->permission->guardName,
            'description' => $this->permission->description,
        ];
    }
}
