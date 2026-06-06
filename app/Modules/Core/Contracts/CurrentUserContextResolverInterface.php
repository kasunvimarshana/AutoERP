<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

use Illuminate\Http\Request;
use Modules\Core\DTOs\CurrentUserContext;

interface CurrentUserContextResolverInterface
{
    public function resolve(Request $request): ?CurrentUserContext;

    public function resolveRequestedTenantId(Request $request): ?int;

    public function hasTenantAccess(CurrentUserContext $context, int $tenantId): bool;
}
