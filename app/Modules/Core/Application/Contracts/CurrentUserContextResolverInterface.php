<?php

declare(strict_types=1);

namespace Modules\Core\Application\Contracts;

use Illuminate\Http\Request;
use Modules\Core\Application\DTO\CurrentUserContext;

interface CurrentUserContextResolverInterface
{
    public function resolve(Request $request): ?CurrentUserContext;

    public function resolveRequestedTenantId(Request $request): ?int;

    public function hasTenantAccess(CurrentUserContext $context, int $tenantId): bool;
}
