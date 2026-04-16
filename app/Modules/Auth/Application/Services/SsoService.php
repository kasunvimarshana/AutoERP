<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Services;

use Laravel\Passport\Token;
use Modules\Auth\Application\Contracts\SsoServiceInterface;
use Modules\Auth\Application\Contracts\TokenServiceInterface;
use Modules\Auth\Domain\Entities\AccessToken;
use Modules\Auth\Domain\Exceptions\AuthenticationException;
use Modules\Auth\Domain\Exceptions\TokenExpiredException;

/**
 * SSO service using Passport personal access tokens as the SSO token mechanism.
 *
 * Cross-application SSO flow:
 *   1. App A calls issueToken() → returns an access token
 *   2. App B exchanges the token via exchangeToken('passport', $token) → gets a local token
 *
 * Additional providers (e.g. Google, GitHub) can be supported by extending this service
 * or replacing the binding in the service provider.
 */
class SsoService implements SsoServiceInterface
{
    public function __construct(
        private readonly TokenServiceInterface $tokenService,
    ) {}

    public function exchangeToken(string $ssoToken, string $provider): AccessToken
    {
        $userId = $this->validateSsoToken($ssoToken, $provider);

        return $this->tokenService->issueToken($userId, 'sso');
    }

    public function validateSsoToken(string $ssoToken, string $provider): int
    {
        if (strtolower($provider) !== 'passport') {
            throw new AuthenticationException("Unsupported SSO provider: {$provider}");
        }

        // Passport stores token IDs as SHA-256 hashes in newer versions.
        // For personal access tokens the plain ID may also be used directly.
        // Try both lookup strategies so the method works with both token formats.
        $token = Token::where('id', hash('sha256', $ssoToken))->first()
               ?? Token::find($ssoToken);

        if (! $token || $token->revoked) {
            throw new TokenExpiredException('SSO token is invalid or has been revoked');
        }

        if ($token->expires_at && $token->expires_at->isPast()) {
            throw new TokenExpiredException('SSO token has expired');
        }

        return (int) $token->user_id;
    }
}
