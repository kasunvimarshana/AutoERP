<?php
namespace Modules\User\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\User\Domain\Entities\User;

class UserResource extends JsonResource
{
    public function __construct(private readonly User $user) { parent::__construct($user); }

    public function toArray($request): array
    {
        return [
            'id'          => $this->user->id,
            'tenant_id'   => $this->user->tenantId,
            'name'        => $this->user->name,
            'email'       => $this->user->email,
            'status'      => $this->user->status,
            'avatar'      => $this->user->avatar,
            'preferences' => $this->user->preferences,
        ];
    }
}
