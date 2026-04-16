<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts;

use Modules\Auth\Domain\Entities\AccessToken;

/**
 * Contract for refreshing an expired or soon-to-expire access token.
 * Revokes the current token and issues a fresh one (token rotation pattern).
 */
interface RefreshTokenServiceInterface
{
    /**
     * Revoke the user's current token and issue a new one.
     */
    public function refresh(int $userId): AccessToken;
}
