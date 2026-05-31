<?php

declare(strict_types=1);

namespace Modules\Pricing\Presentation\Http\Requests\Concerns;

use Modules\Core\Application\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;

trait ResolvesPricingTenant
{
    protected function prepareForValidation(): void
    {
        $merge = [];

        if (! $this->filled('tenant_id')) {
            $tenantId = app(CurrentTenantContextAccessorInterface::class)->currentTenantId();

            if ($tenantId !== null) {
                $merge['tenant_id'] = (int) $tenantId;
            }
        }

        if (! $this->filled('organization_unit_id')) {
            $organizationUnitId = app(CurrentOrganizationUnitContextAccessorInterface::class)->currentOrganizationUnitId();

            if ($organizationUnitId !== null) {
                $merge['organization_unit_id'] = (int) $organizationUnitId;
            }
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }
}
