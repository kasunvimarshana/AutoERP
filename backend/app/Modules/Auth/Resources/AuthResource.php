<?php

namespace App\Modules\Auth\Resources;

use App\Modules\User\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'access_token' => $this->resource['access_token'],
            'token_type'   => $this->resource['token_type'] ?? 'Bearer',
            'expires_at'   => $this->resource['expires_at'] ?? null,
            'user'         => new UserResource($this->resource['user']),
        ];
    }
}
