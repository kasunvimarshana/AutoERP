<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Application\Contracts\RefreshTokenServiceInterface;
use Modules\Auth\Application\Contracts\TokenServiceInterface;
use Modules\Auth\Domain\Entities\AccessToken;

/**
 * Token-rotation refresh service.
 *
 * Revokes the caller's current access token and issues a fresh one.
 * Compatible with the Passport personal-access-token pattern used throughout
 * the application; no OAuth2 grant-type changes are required.
 *
 * Swappable via RefreshTokenServiceInterface binding in AuthModuleServiceProvider.
 */
class RefreshTokenService implements RefreshTokenServiceInterface
{
    public function __construct(
        private readonly TokenServiceInterface $tokenService,
    ) {}

    public function refresh(int $userId): AccessToken
    {
        return DB::transaction(function () use ($userId): AccessToken {
            // Revoke the current token before issuing a replacement.
            $this->tokenService->revokeCurrentToken($userId);

            return $this->tokenService->issueToken($userId);
        });
    }
}
