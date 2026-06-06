<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\Contracts\RepositoryPortInterface;

interface AuthAccessTokenRepositoryInterface extends RepositoryPortInterface
{
    public function findActiveByTokenKey(?int $tenantId, string $tokenKey): ?DataRecord;

    public function revokeBySessionId(int $sessionId, ?int $tenantId = null): void;
}
