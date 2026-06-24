<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

interface PlatformPermissionCheckerInterface
{
    public function hasPermission(int $userId, string $permission): bool;

    /** @return list<string> */
    public function permissions(int $userId): array;
}
