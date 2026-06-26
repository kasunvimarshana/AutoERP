<?php

declare(strict_types=1);

namespace Modules\User\Http\Resources\Platform;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\User\Models\PlatformOperatorPermissionModel;

final class PlatformOperatorResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $permissions = $this->resource->relationLoaded('permissionAssignments')
            ? $this->resource->permissionAssignments
                ->map(fn (PlatformOperatorPermissionModel $assignment): ?string => $assignment->permission?->name)
                ->filter()
                ->sort()
                ->values()
                ->all()
            : [];

        return [
            'id' => (int) $this->resource->getKey(),
            'first_name' => (string) $this->resource->getAttribute('first_name'),
            'last_name' => $this->resource->getAttribute('last_name'),
            'display_name' => trim((string) $this->resource->getAttribute('first_name').' '.(string) $this->resource->getAttribute('last_name')),
            'email' => (string) $this->resource->getAttribute('email'),
            'status' => (string) $this->resource->getAttribute('status'),
            'invitation' => $this->invitation(),
            'permissions' => $permissions,
            'row_version' => (int) $this->resource->getAttribute('row_version'),
            'created_at' => $this->resource->getAttribute('created_at')?->toISOString(),
            'updated_at' => $this->resource->getAttribute('updated_at')?->toISOString(),
        ];
    }

    /** @return array<string,mixed>|null */
    private function invitation(): ?array
    {
        if (! $this->resource->relationLoaded('latestInvitation')) {
            return null;
        }

        $invitation = $this->resource->latestInvitation;
        if (! $invitation instanceof \Modules\User\Models\PlatformOperatorInvitationModel) {
            return null;
        }
        $delivery = $invitation->relationLoaded('deliveries')
            ? $invitation->deliveries->sortByDesc('attempt_number')->first()
            : null;

        return [
            'status' => (string) $invitation->getAttribute('status'),
            'delivery_status' => $delivery?->getAttribute('status'),
            'expires_at' => $invitation->getAttribute('expires_at')?->toISOString(),
            'sent_at' => $delivery?->getAttribute('sent_at')?->toISOString(),
            'failed_at' => $delivery?->getAttribute('failed_at')?->toISOString(),
            'error_message' => $delivery?->getAttribute('error_message'),
        ];
    }
}