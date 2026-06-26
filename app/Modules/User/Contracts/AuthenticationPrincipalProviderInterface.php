<?php

declare(strict_types=1);

namespace Modules\User\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface AuthenticationPrincipalProviderInterface
{
    public function tenantPrincipal(int $tenantId, int $userId): ?Authenticatable;

    public function platformPrincipal(int $operatorId): ?Authenticatable;
}
