<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use Modules\Core\Contracts\RepositoryPortInterface;
use Modules\Core\DTOs\DataRecord;

interface AuthPlatformAccessTokenRepositoryInterface extends RepositoryPortInterface
{
    public function findActiveByTokenKey(string $tokenKey): ?DataRecord;

    public function revokeByPlatformSessionId(int $platformSessionId): void;
}
