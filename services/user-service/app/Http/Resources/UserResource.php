<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int              $id
 * @property string|null      $keycloak_id
 * @property string           $email
 * @property string|null      $first_name
 * @property string|null      $last_name
 * @property string|null      $username
 * @property array<string>    $roles
 * @property bool             $is_active
 * @property \Carbon\Carbon|null $last_login_at
 * @property array<mixed>|null   $preferences
 * @property string|null      $avatar_url
 * @property string|null      $phone
 * @property string|null      $department
 * @property \Carbon\Carbon   $created_at
 * @property \Carbon\Carbon   $updated_at
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'keycloak_id'   => $this->keycloak_id,
            'email'         => $this->email,
            'first_name'    => $this->first_name,
            'last_name'     => $this->last_name,
            'full_name'     => $this->full_name,
            'username'      => $this->username,
            'roles'         => $this->roles ?? [],
            'is_active'     => $this->is_active,
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'preferences'   => $this->preferences ?? [],
            'avatar_url'    => $this->avatar_url,
            'phone'         => $this->phone,
            'department'    => $this->department,
            'created_at'    => $this->created_at?->toIso8601String(),
            'updated_at'    => $this->updated_at?->toIso8601String(),
        ];
    }

    public function with(Request $request): array
    {
        return [
            'meta' => [
                'service' => 'user-service',
                'version' => '1.0.0',
            ],
        ];
    }
}
