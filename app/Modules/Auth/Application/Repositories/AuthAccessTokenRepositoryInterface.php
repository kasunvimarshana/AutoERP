<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Repositories;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

interface AuthAccessTokenRepositoryInterface extends RepositoryPortInterface
{
    public function findActiveByTokenKey(?int $tenantId, string $tokenKey): ?DataRecord;

    public function revokeBySessionId(int $sessionId, ?int $tenantId = null): void;
}
