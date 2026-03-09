<?php

namespace App\Infrastructure\Auth;

use App\Domain\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\PersonalAccessTokenResult;
use Laravel\Passport\Token;

class PassportTokenDriver
{
    /**
     * Issue a new personal access token with tenant claims embedded.
     */
    public function issue(User $user, string $tokenName, array $scopes = ['*'], array $claims = []): array
    {
        // Encode tenant claims as scopes with a 'claims:' prefix
        $allScopes = $scopes;
        foreach ($claims as $key => $value) {
            if ($value !== null) {
                $allScopes[] = "claims:{$key}:{$value}";
            }
        }

        /** @var PersonalAccessTokenResult $result */
        $result    = $user->createToken($tokenName, $allScopes);
        $expiresIn = $this->getExpirationSeconds();

        return [
            'access_token'  => $result->accessToken,
            'refresh_token' => null,
            'expires_in'    => $expiresIn,
            'token_type'    => 'Bearer',
            'token_id'      => $result->token->id,
            'token_model'   => $result->token,
        ];
    }

    /**
     * Revoke a token by its ID.
     */
    public function revoke(string $tokenId): bool
    {
        return (bool) DB::table('oauth_access_tokens')
            ->where('id', $tokenId)
            ->update(['revoked' => true]);
    }

    /**
     * Revoke all tokens for a given user.
     */
    public function revokeAllForUser(string $userId): int
    {
        return DB::table('oauth_access_tokens')
            ->where('user_id', $userId)
            ->where('revoked', false)
            ->update(['revoked' => true]);
    }

    /**
     * Find a Token by its ID.
     */
    public function findById(string $tokenId): ?Token
    {
        return Token::find($tokenId);
    }

    /**
     * Extract tenant claims from a token's scopes.
     *
     * @return array<string, string>
     */
    public function extractClaims(Token $token): array
    {
        $claims = [];

        foreach ($token->scopes as $scope) {
            if (str_starts_with($scope, 'claims:')) {
                $parts = explode(':', $scope, 3);
                if (count($parts) === 3) {
                    $claims[$parts[1]] = $parts[2];
                }
            }
        }

        return $claims;
    }

    /**
     * Get token expiration in seconds.
     */
    private function getExpirationSeconds(): int
    {
        $expiry = config('passport.tokens_expire_in');

        if ($expiry instanceof \DateInterval) {
            return (int) (new \DateTime())->add($expiry)->getTimestamp() - time();
        }

        return 86400 * 15; // Default: 15 days
    }
}
