<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

interface PlatformPermissionDirectoryInterface
{
    /** @return list<string> */
    public function permissions(int $operatorId): array;
}
