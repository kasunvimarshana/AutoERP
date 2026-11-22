<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Core\Contracts;

use Illuminate\Http\Request;
use Modules\Core\DTOs\CurrentTenantContext;

interface CurrentTenantContextResolverInterface
{
    public function resolve(Request $request): ?CurrentTenantContext;

    public function hasAccess(Request $request, CurrentTenantContext $context): bool;
}
