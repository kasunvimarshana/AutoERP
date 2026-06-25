<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use Modules\Core\Contracts\RepositoryPortInterface;
use Modules\Core\DTOs\DataRecord;

interface AuthRefreshTokenRepositoryInterface extends RepositoryPortInterface
{
    public function findActiveByRefreshKey(?int $tenantId, string $refreshKey): ?DataRecord;

    public function revokeBySessionId(int $sessionId, ?int $tenantId = null): void;
}
