<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\Token;

class AuthService
{
    public function __construct(private readonly UserRepositoryInterface $userRepository) {}

    // -------------------------------------------------------------------------
    // Login
    // -------------------------------------------------------------------------

    /**
     * Validate credentials and issue a Passport personal-access token.
     *
     * @return array{user: User, access_token: string, token_type: string, expires_at: string}
     * @throws AuthenticationException
     */
    public function login(string $email, string $password, ?string $tenantId = null): array
    {
        $user = $this->userRepository->findByEmail($email);

        if (! $user || ! Hash::check($password, $user->password)) {
            throw new AuthenticationException('Invalid credentials.');
        }

        if (! $user->is_active) {
            throw new AuthenticationException('Account is deactivated.');
        }

        // SSO-only accounts cannot log in with a password
        if ($user->sso_provider !== null) {
            throw new AuthenticationException('This account uses SSO. Please log in via your identity provider.');
        }

        // Enforce tenant isolation on login when a tenant header is supplied
        if ($tenantId !== null && (string) $user->tenant_id !== (string) $tenantId) {
            throw new AuthenticationException('User does not belong to the specified tenant.');
        }

        $tokenResult = $user->createToken('Personal Access Token');
        $token       = $tokenResult->token;
        $token->save();

        Log::info('User logged in', ['user_id' => $user->id, 'tenant_id' => $user->tenant_id]);

        return [
            'user'         => $user->load(['roles', 'permissions', 'tenant']),
            'access_token' => $tokenResult->accessToken,
            'token_type'   => 'Bearer',
            'expires_at'   => optional($token->expires_at)->toDateTimeString(),
        ];
    }

    // -------------------------------------------------------------------------
    // Logout
    // -------------------------------------------------------------------------

    public function logout(User $user): void
    {
        /** @var Token $token */
        $token = $user->token();
        if ($token) {
            $token->revoke();
        }

        Log::info('User logged out', ['user_id' => $user->id]);
    }

    // -------------------------------------------------------------------------
    // Refresh – revoke current token, issue a new one
    // -------------------------------------------------------------------------

    /**
     * @return array{access_token: string, token_type: string, expires_at: string}
     */
    public function refresh(User $user): array
    {
        /** @var Token $token */
        $token = $user->token();
        if ($token) {
            $token->revoke();
        }

        $tokenResult = $user->createToken('Personal Access Token');
        $newToken    = $tokenResult->token;
        $newToken->save();

        Log::info('Token refreshed', ['user_id' => $user->id]);

        return [
            'access_token' => $tokenResult->accessToken,
            'token_type'   => 'Bearer',
            'expires_at'   => optional($newToken->expires_at)->toDateTimeString(),
        ];
    }

    // -------------------------------------------------------------------------
    // SSO – exchange an SSO assertion/token for a local Passport token
    // -------------------------------------------------------------------------

    /**
     * Upsert a user record from an SSO provider payload and return a Passport token.
     *
     * @param  array{email: string, name: string, provider: string, provider_id: string, tenant_id?: int}  $ssoPayload
     * @return array{user: User, access_token: string, token_type: string, expires_at: string}
     */
    public function ssoLogin(array $ssoPayload): array
    {
        $user = User::firstOrCreate(
            ['email' => $ssoPayload['email']],
            [
                'name'         => $ssoPayload['name'],
                // SSO-only users have no usable password (bcrypt of random string
                // produces an invalid hash that can never be matched by Hash::check)
                'password'     => Hash::make(\Illuminate\Support\Str::random(64)),
                'tenant_id'    => $ssoPayload['tenant_id'] ?? null,
                'is_active'    => true,
                'sso_provider' => $ssoPayload['provider'],
                'sso_id'       => $ssoPayload['provider_id'],
            ]
        );

        // Sync SSO metadata on subsequent logins
        if (! $user->wasRecentlyCreated) {
            $user->update([
                'sso_provider' => $ssoPayload['provider'],
                'sso_id'      => $ssoPayload['provider_id'],
            ]);
        }

        $tokenResult = $user->createToken('SSO Token');
        $token       = $tokenResult->token;
        $token->save();

        Log::info('SSO login', [
            'user_id'  => $user->id,
            'provider' => $ssoPayload['provider'],
        ]);

        return [
            'user'         => $user->load(['roles', 'permissions', 'tenant']),
            'access_token' => $tokenResult->accessToken,
            'token_type'   => 'Bearer',
            'expires_at'   => optional($token->expires_at)->toDateTimeString(),
        ];
    }

    // -------------------------------------------------------------------------
    // Current authenticated user
    // -------------------------------------------------------------------------

    public function me(): ?User
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->load(['roles', 'permissions', 'tenant']);
    }
}
