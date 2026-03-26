<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        // $resource = $this->resource;
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'email' => $this->getEmail()->value(),
            'first_name' => $this->getFirstName(),
            'last_name' => $this->getLastName(),
            'full_name' => $this->getFullName(),
            'phone' => $this->getPhone()?->value(),
            'address' => $this->getAddress()?->toArray(),
            'preferences' => $this->getPreferences()->toArray(),
            'active' => $this->isActive(),
            'roles' => RoleResource::collection($this->getRoles()),
            'created_at' => $this->getCreatedAt()->format('c'),
            'updated_at' => $this->getUpdatedAt()->format('c'),
        ];
    }
}
