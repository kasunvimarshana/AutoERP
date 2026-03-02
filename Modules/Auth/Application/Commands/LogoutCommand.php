<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Commands;

/**
 * Immutable write DTO for token revocation on logout.
 * Carries the raw Bearer token string so the repository can locate and
 * delete the exact token without any Eloquent access in the controller layer.
 */
final readonly class LogoutCommand
{
    public function __construct(
        public readonly int $userId,
        public readonly int $tenantId,
        public readonly ?string $bearerToken,
    ) {}
}
