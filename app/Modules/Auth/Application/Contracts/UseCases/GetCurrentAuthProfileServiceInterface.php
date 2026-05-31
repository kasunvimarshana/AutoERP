<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts\UseCases;

use Modules\Core\Application\Results\Result;

interface GetCurrentAuthProfileServiceInterface
{
    /**
     * @param array<string,mixed> $tokenPayload
     */
    public function getProfile(
        int $userId,
        ?int $tenantId,
        ?int $organizationUnitId,
        ?string $guard,
        ?string $provider,
        ?string $applicationId,
        array $tokenPayload,
    ): Result;
}
