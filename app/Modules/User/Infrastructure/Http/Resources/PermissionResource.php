<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
    public function toArray($request)
    {
        // $resource = $this->resource;
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'name' => $this->getName(),
        ];
    }
}
