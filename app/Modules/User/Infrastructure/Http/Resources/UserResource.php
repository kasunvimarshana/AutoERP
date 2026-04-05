<?php
declare(strict_types=1);
namespace Modules\User\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\User\Domain\Entities\User;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var User $user */
        $user = $this->resource;
        return [
            'id' => $user->getId(),
            'tenant_id' => $user->getTenantId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'status' => $user->getStatus(),
            'phone' => $user->getPhone(),
            'avatar' => $user->getAvatar(),
            'preferences' => $user->getPreferences(),
            'email_verified_at' => $user->getEmailVerifiedAt()?->format('Y-m-d H:i:s'),
            'created_at' => $user->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at' => $user->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
