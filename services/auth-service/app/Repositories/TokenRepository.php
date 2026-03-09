<?php

namespace App\Repositories;

use App\Domain\Contracts\TokenRepositoryInterface;
use App\Domain\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Token;
use Laravel\Passport\TokenRepository as PassportTokenRepository;

class TokenRepository implements TokenRepositoryInterface
{
    public function __construct(
        private readonly PassportTokenRepository $passportTokenRepository,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function createForUser(
        User $user,
        string $tokenName,
        array $scopes = [],
        array $claims = []
    ): array {
        // Embed tenant claims as special scopes (prefixed with 'claims:')
        $allScopes = $scopes;
        foreach ($claims as $key => $value) {
            if ($value !== null) {
                $allScopes[] = "claims:{$key}:{$value}";
            }
        }

        $tokenResult  = $user->createToken($tokenName, $allScopes);
        $accessToken  = $tokenResult->accessToken;
        $tokenModel   = $tokenResult->token;
        $expiresIn    = config('passport.tokens_expire_in')
            ? (int) config('passport.tokens_expire_in')->totalSeconds
            : 86400;

        return [
            'access_token'  => $accessToken,
            'refresh_token' => null, // Passport personal tokens do not have refresh tokens
            'expires_in'    => $expiresIn,
            'token_model'   => $tokenModel,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function revoke(string $tokenId): bool
    {
        return (bool) DB::table('oauth_access_tokens')
            ->where('id', $tokenId)
            ->update(['revoked' => true]);
    }

    /**
     * {@inheritdoc}
     */
    public function revokeAllForUser(string $userId, ?string $deviceId = null): int
    {
        $query = DB::table('oauth_access_tokens')
            ->where('user_id', $userId)
            ->where('revoked', false);

        if ($deviceId !== null) {
            $query->where('name', "device:{$deviceId}");
        }

        return $query->update(['revoked' => true]);
    }

    /**
     * {@inheritdoc}
     */
    public function findValid(string $token): ?Token
    {
        // Hash the token to find it in the database
        $tokenId = substr($token, 0, 80);

        $record = DB::table('oauth_access_tokens')
            ->where('id', $tokenId)
            ->where('revoked', false)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$record) {
            return null;
        }

        return Token::find($record->id);
    }

    /**
     * {@inheritdoc}
     */
    public function getActiveForUser(string $userId): Collection
    {
        return Token::where('user_id', $userId)
                    ->where('revoked', false)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    })
                    ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function pruneExpired(): int
    {
        return DB::table('oauth_access_tokens')
            ->where('expires_at', '<', now())
            ->delete();
    }
}
