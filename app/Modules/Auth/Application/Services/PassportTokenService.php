<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Auth\Application\Contracts\AuthUserRepositoryInterface;
use Modules\Auth\Application\Contracts\TokenServiceInterface;
use Modules\Auth\Domain\Entities\AccessToken;
use Modules\Auth\Domain\Exceptions\InvalidCredentialsException;

/**
 * Passport-backed token service.
 * Uses Laravel Passport's Personal Access Tokens for issuance/revocation.
 * Swappable via TokenServiceInterface binding in the service provider.
 */
class PassportTokenService implements TokenServiceInterface
{
    public function __construct(
        private readonly AuthUserRepositoryInterface $userRepository,
    ) {}

    public function issueToken(int $userId, string $tokenName = 'api', array $scopes = []): AccessToken
    {
        $user = $this->userRepository->findForPassport($userId);

        if (! $user) {
            throw new InvalidCredentialsException('User not found for token issuance');
        }

        $result = $user->createToken($tokenName, $scopes);

        return new AccessToken(
            accessToken: $result->accessToken,
            tokenType: 'Bearer',
            expiresIn: (int) config('auth.passport.token_expiry_days', 15) * 24 * 3600,
            scopes: $scopes,
        );
    }

    public function revokeCurrentToken(int $userId): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        $token = $user->currentAccessToken();
        if (! $token) {
            return false;
        }

        $token->revoke();

        return true;
    }

    public function revokeAllTokens(int $userId): bool
    {
        $user = $this->userRepository->findForPassport($userId);
        if (! $user) {
            return false;
        }

        $user->tokens()->each(fn ($token) => $token->revoke());

        return true;
    }
}
