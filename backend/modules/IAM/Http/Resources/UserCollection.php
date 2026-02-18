<?php

namespace Modules\IAM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'users' => $this->collection->map(fn($user) => new UserResource($user)),
            'summary' => [
                'total_count' => $this->collection->count(),
                'active_users' => $this->collection->where('is_active', true)->count(),
                'verified_users' => $this->collection->whereNotNull('email_verified_at')->count(),
                'mfa_enabled_count' => $this->collection->where('mfa_enabled', true)->count(),
            ],
        ];
    }
}
