<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts\UseCases;

use Modules\Core\Application\Results\Result;

interface RevokeSessionServiceInterface
{
    public function revokeSession(int|string $sessionId, ?int $tenantId = null): Result;
}
