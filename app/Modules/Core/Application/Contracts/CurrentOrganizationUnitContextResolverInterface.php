<?php

declare(strict_types=1);

namespace Modules\Core\Application\Contracts;

use Illuminate\Http\Request;
use Modules\Core\Application\DTO\CurrentOrganizationUnitContext;

interface CurrentOrganizationUnitContextResolverInterface
{
    public function resolve(Request $request): ?CurrentOrganizationUnitContext;

    public function hasAccess(Request $request, CurrentOrganizationUnitContext $context): bool;
}
