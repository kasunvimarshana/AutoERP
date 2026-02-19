<?php

namespace App\Services;

use App\Contracts\Services\AuthServiceInterface;
use App\Enums\AuditAction;
use App\Models\User;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function login(array $credentials, string $guardName = 'api'): array
    {
        $token = auth($guardName)->attempt($credentials);

        if (! $token) {
            throw new \InvalidArgumentException('Invalid credentials.');
        }

        $user = auth($guardName)->user();

        $user->update(['last_login_at' => now()]);

        $this->auditService->log(
            action: AuditAction::Login,
            auditableType: User::class,
            auditableId: $user->id,
            newValues: ['ip' => request()->ip()]
        );

        return $this->buildTokenResponse($token, $guardName);
    }

    public function logout(string $guardName = 'api'): void
    {
        $user = auth($guardName)->user();

        if ($user) {
            $this->auditService->log(
                action: AuditAction::Logout,
                auditableType: User::class,
                auditableId: $user->id
            );
        }

        auth($guardName)->logout();
    }

    public function refresh(string $guardName = 'api'): array
    {
        try {
            $token = auth($guardName)->refresh();
        } catch (JWTException $e) {
            throw new \RuntimeException('Token refresh failed: '.$e->getMessage());
        }

        return $this->buildTokenResponse($token, $guardName);
    }

    public function me(string $guardName = 'api'): array
    {
        $user = auth($guardName)->user();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'tenant_id' => $user->tenant_id,
            'organization_id' => $user->organization_id,
            'status' => $user->status?->value,
            'locale' => $user->locale,
            'timezone' => $user->timezone,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ];
    }

    private function buildTokenResponse(string $token, string $guardName): array
    {
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth($guardName)->factory()->getTTL() * 60,
        ];
    }
}
