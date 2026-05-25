<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Repositories;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

interface AuthRefreshTokenRepositoryInterface extends RepositoryPortInterface
{
    public function findActiveByRefreshKey(?int $tenantId, string $refreshKey): ?DataRecord;

    public function revokeBySessionId(int $sessionId, ?int $tenantId = null): void;
}
