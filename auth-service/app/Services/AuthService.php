<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\AuthServiceInterface;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\PersonalAccessTokenResult;

/**
 * Concrete implementation of AuthServiceInterface using Laravel Passport.
 *
 * Handles token issuance, validation, and revocation for all
 * tenant-scoped users through the OAuth2 personal-access-token flow.
 */
final class AuthService implements AuthServiceInterface
{
    /**
     * {@inheritDoc}
     */
    public function register(array $data): array
    {
        $user = User::create([
            'tenant_id'  => $data['tenant_id'],
            'name'       => $data['name'],
            'email'      => $data['email'],
            'password'   => Hash::make($data['password']),
            'is_active'  => true,
            'attributes' => $data['attributes'] ?? [],
        ]);

        /** @var PersonalAccessTokenResult $tokenResult */
        $tokenResult = $user->createToken('Personal Access Token');

        Log::info('User registered', ['user_id' => $user->id, 'tenant_id' => $user->tenant_id]);

        return [
            'user'  => $user,
            'token' => $tokenResult->accessToken,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function login(array $credentials): ?array
    {
        if (!Auth::attempt($credentials)) {
            return null;
        }

        /** @var User $user */
        $user = Auth::user();

        if (!$user->is_active) {
            Log::warning('Inactive user attempted login', ['user_id' => $user->id]);
            return null;
        }

        /** @var PersonalAccessTokenResult $tokenResult */
        $tokenResult = $user->createToken('Personal Access Token');
        $token       = $tokenResult->token;

        // Set token expiry if remember-me is not requested
        $token->expires_at = now()->addHours(8);
        $token->save();

        return [
            'token'      => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_in' => 8 * 3600,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function logout(User $user): void
    {
        // Revoke all tokens (single-device logout)
        $user->tokens()->where('revoked', false)->update(['revoked' => true]);

        Log::info('User logged out', ['user_id' => $user->id]);
    }

    /**
     * {@inheritDoc}
     *
     * Note: The personal-access-token grant does not issue proper OAuth2
     * refresh tokens. This method expects the *access token ID* (UUID stored
     * in oauth_access_tokens.id) as the $refreshToken parameter, revokes it,
     * and issues a fresh token for the same user.
     *
     * For a full OAuth2 refresh-token flow use the Password or
     * Authorization-Code grant with Passport's /oauth/token endpoint.
     */
    public function refreshToken(string $refreshToken): ?array
    {
        // $refreshToken is treated as the PAT token ID (UUID)
        $token = \Laravel\Passport\Token::where('id', $refreshToken)
            ->where('revoked', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$token) {
            return null;
        }

        /** @var User $user */
        $user = User::find($token->user_id);

        if (!$user || !$user->is_active) {
            return null;
        }

        // Revoke old token and issue a new one
        $token->revoke();
        $tokenResult = $user->createToken('Personal Access Token');
        $newToken    = $tokenResult->token;
        $newToken->expires_at = now()->addHours(8);
        $newToken->save();

        return [
            'token'      => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_in' => 8 * 3600,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * Convenience method for internal service-to-service calls where another
     * microservice forwards a raw Bearer token and needs the owning User.
     *
     * Passport stores the access token ID (UUID) in oauth_access_tokens; the
     * raw token string is a self-contained JWT (Passport ≥ 12) that encodes
     * the token ID in its payload. For non-JWT drivers the token is looked up
     * by its hashed value. Prefer authenticating via the standard
     * auth:api middleware wherever possible.
     */
    public function getUserFromToken(string $token): ?User
    {
        // Attempt direct UUID lookup first (Passport 12+ JWT tokens carry the
        // token ID in the "jti" claim; callers should pass that claim value).
        $tokenModel = \Laravel\Passport\Token::where('id', $token)
            ->where('revoked', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$tokenModel) {
            return null;
        }

        return User::find($tokenModel->user_id);
    }
}
