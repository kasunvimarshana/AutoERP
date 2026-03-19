<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Log;
use Enterprise\Core\Security\ImmutableAuditLog;

/**
 * LoginService - Handles authentication and JWT token generation.
 * Enforces strict multi-tenant isolation and security auditing.
 */
class LoginService
{
    /**
     * Authenticate user and issue JWT.
     */
    public function authenticate(string $email, string $password, string $tenantId)
    {
        // 1. Locate user within specific tenant (Multi-tenant isolation)
        $user = User::where('email', $email)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            ImmutableAuditLog::log('User', 'LOGIN_FAILED', [], ['email' => $email], ['tenant_id' => $tenantId]);
            throw new \Exception("Invalid credentials for this tenant.");
        }

        // 2. Prepare JWT Claims with full tenant hierarchy context
        $payload = [
            'iss' => config('app.url'),
            'iat' => time(),
            'exp' => time() + config('enterprise.auth.ttl', 3600), // Default 1 hour
            'sub' => $user->id,
            'tenant_id' => $user->tenant_id,
            'organization_id' => $user->organization_id,
            'branch_id' => $user->branch_id,
            'role' => $user->role->slug ?? 'user',
            'permissions' => $user->role->permissions->pluck('slug')->toArray() ?? [],
        ];

        // 3. Sign the JWT using the private key (RSA)
        $privateKey = config('enterprise.auth.private_key');
        $token = JWT::encode($payload, $privateKey, 'RS256');

        // 4. Record successful login (Security compliance)
        ImmutableAuditLog::log('User', 'LOGIN_SUCCESS', [], ['user_id' => $user->id], ['tenant_id' => $tenantId]);

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => config('enterprise.auth.ttl', 3600),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'tenant_id' => $user->tenant_id,
                'role' => $user->role->slug ?? 'user',
            ],
        ];
    }
}
