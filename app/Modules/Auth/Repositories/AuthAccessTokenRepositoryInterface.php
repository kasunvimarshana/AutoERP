<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use Modules\Core\Contracts\RepositoryPortInterface;
use Modules\Core\DTOs\DataRecord;

interface AuthAccessTokenRepositoryInterface extends RepositoryPortInterface
{
    public function findActiveByTokenKey(string $tokenKey): ?DataRecord;

    public function revokeBySessionId(int $sessionId, int $tenantId): void;
}
