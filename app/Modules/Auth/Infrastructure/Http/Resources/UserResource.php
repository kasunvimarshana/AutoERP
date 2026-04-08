<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Auth\Domain\Entities\User;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;

        return [
            'id'          => $user->id,
            'tenant_id'   => $user->tenantId,
            'name'        => $user->name,
            'email'       => $user->email,
            'role'        => $user->role,
            'status'      => $user->status,
            'preferences' => $user->preferences,
            'created_at'  => $user->createdAt->format(\DateTimeInterface::ATOM),
            'updated_at'  => $user->updatedAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
