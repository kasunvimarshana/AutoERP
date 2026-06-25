<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use Modules\Core\Contracts\RepositoryPortInterface;
use Modules\Core\DTOs\DataRecord;

interface AuthPlatformRefreshTokenRepositoryInterface extends RepositoryPortInterface
{
    public function findActiveByRefreshKey(string $refreshKey): ?DataRecord;

    public function rotateIfActive(int $id, int $rowVersion): bool;

    public function revokeByPlatformSessionId(int $platformSessionId): void;
}
