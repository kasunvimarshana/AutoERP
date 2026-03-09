<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\User\UserResource;
use App\Http\Resources\Tenant\TenantResource;

class AuthResource extends JsonResource
{
    private string $accessToken;
    private ?string $refreshToken;
    private string $tokenType;
    private int $expiresIn;

    public function __construct(
        $resource,
        string $accessToken,
        ?string $refreshToken = null,
        string $tokenType = 'Bearer',
        int $expiresIn = 0
    ) {
        parent::__construct($resource);
        $this->accessToken  = $accessToken;
        $this->refreshToken = $refreshToken;
        $this->tokenType    = $tokenType;
        $this->expiresIn    = $expiresIn;
    }

    public function toArray(Request $request): array
    {
        $user   = $this->resource;
        $tenant = $user->tenant;

        return [
            'access_token'  => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'token_type'    => $this->tokenType,
            'expires_in'    => $this->expiresIn,
            'user'          => new UserResource($user),
            'tenant'        => $tenant ? new TenantResource($tenant) : null,
            'permissions'   => $user->getAllPermissions()->pluck('name')->values(),
            'roles'         => $user->getRoleNames()->values(),
        ];
    }
}
