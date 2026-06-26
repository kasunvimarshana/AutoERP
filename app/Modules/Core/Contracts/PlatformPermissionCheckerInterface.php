<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

interface PlatformPermissionCheckerInterface
{
    public function allows(int $operatorId, string $permission): bool;
}
