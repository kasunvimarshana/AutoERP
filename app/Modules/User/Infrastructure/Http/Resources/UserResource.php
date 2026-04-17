<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class UserResource extends JsonResource
{
    public function __construct(
        mixed $resource,
        private readonly ?Collection $attachments = null,
        private readonly ?Collection $devices = null,
        private readonly bool $includePermissions = false
    ) {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        $attachments = $this->attachments;
        if ($attachments === null && $this->hasLoadedAttachments()) {
            $attachments = $this->getAttachments();
        }

        $devices = $this->devices;
        if ($devices === null && $this->hasLoadedDevices()) {
            $devices = $this->getDevices();
        }

        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'org_unit_id' => $this->getOrgUnitId(),
            'email' => $this->getEmail()->value(),
            'first_name' => $this->getFirstName(),
            'last_name' => $this->getLastName(),
            'full_name' => $this->getFullName(),
            'phone' => $this->getPhone()?->value(),
            'avatar' => $this->getAvatar(),
            'address' => $this->getAddress()?->toArray(),
            'preferences' => $this->getPreferences()->toArray(),
            'active' => $this->isActive(),
            'roles' => RoleResource::collection($this->getRoles()),
            'permissions' => $this->when(
                $this->includePermissions,
                fn (): \Illuminate\Http\Resources\Json\AnonymousResourceCollection => PermissionResource::collection($this->collectPermissions())
            ),
            'attachments' => $this->when(
                $attachments !== null,
                fn (): \Illuminate\Http\Resources\Json\AnonymousResourceCollection => UserAttachmentResource::collection($attachments)
            ),
            'devices' => $this->when(
                $devices !== null,
                fn (): \Illuminate\Http\Resources\Json\AnonymousResourceCollection => UserDeviceResource::collection($devices)
            ),
            'created_at' => $this->getCreatedAt()->format('c'),
            'updated_at' => $this->getUpdatedAt()->format('c'),
        ];
    }

    private function collectPermissions(): Collection
    {
        return $this->getRoles()
            ->flatMap(fn ($role): Collection => $role->getPermissions())
            ->unique(fn ($permission): ?int => $permission->getId())
            ->values();
    }
}
