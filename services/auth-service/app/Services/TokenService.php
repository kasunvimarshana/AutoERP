<?php

namespace App\Services;

use App\Domain\Contracts\TokenRepositoryInterface;
use App\Domain\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Token;

class TokenService
{
    public function __construct(
        private readonly TokenRepositoryInterface $tokenRepository,
    ) {}

    /**
     * Create a new token for a user with tenant claims.
     *
     * @return array{access_token: string, refresh_token: string|null, expires_in: int, token_model: Token|null}
     */
    public function createForUser(
        User $user,
        string $tokenName,
        array $scopes = ['*'],
        array $claims = []
    ): array {
        return $this->tokenRepository->createForUser($user, $tokenName, $scopes, $claims);
    }

    /**
     * Refresh an access token using a refresh token.
     *
     * This proxies the OAuth2 token refresh endpoint internally.
     *
     * @return array{access_token: string, refresh_token: string, expires_in: int}
     */
    public function refresh(string $refreshToken): array
    {
        $http = new \GuzzleHttp\Client([
            'base_uri'        => config('app.url'),
            'verify'          => false,
            'timeout'         => 10,
            'connect_timeout' => 5,
        ]);

        try {
            $response = $http->post('/oauth/token', [
                'form_params' => [
                    'grant_type'    => 'refresh_token',
                    'refresh_token' => $refreshToken,
                    'client_id'     => config('passport.personal_access_client.id'),
                    'client_secret' => config('passport.personal_access_client.secret'),
                    'scope'         => '',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'access_token'  => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'expires_in'    => $data['expires_in'] ?? 0,
            ];
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $body = json_decode($e->getResponse()->getBody()->getContents(), true);
            throw new \RuntimeException(
                $body['message'] ?? 'Token refresh failed.',
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Revoke a specific token by its ID.
     */
    public function revoke(string $tokenId): bool
    {
        return $this->tokenRepository->revoke($tokenId);
    }

    /**
     * Revoke all tokens for a user.
     */
    public function revokeAllForUser(string $userId, ?string $deviceId = null): int
    {
        return $this->tokenRepository->revokeAllForUser($userId, $deviceId);
    }

    /**
     * Get a user from their access token string.
     */
    public function getUserFromToken(string $token): ?User
    {
        $tokenModel = $this->tokenRepository->findValid($token);

        if ($tokenModel === null) {
            return null;
        }

        return User::find($tokenModel->user_id);
    }

    /**
     * Get all active tokens for a user.
     */
    public function getActiveTokensForUser(string $userId): \Illuminate\Support\Collection
    {
        return $this->tokenRepository->getActiveForUser($userId);
    }

    /**
     * Prune expired tokens (should be run via scheduled command).
     */
    public function pruneExpiredTokens(): int
    {
        return $this->tokenRepository->pruneExpired();
    }
}
