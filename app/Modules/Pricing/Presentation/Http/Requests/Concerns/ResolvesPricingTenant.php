<?php

declare(strict_types=1);

namespace Modules\Pricing\Presentation\Http\Requests\Concerns;

use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;

trait ResolvesPricingTenant
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('tenant_id')) {
            return;
        }

        $tenantId = app(CurrentTenantContextAccessorInterface::class)->currentTenantId();

        if ($tenantId !== null) {
            $this->merge(['tenant_id' => (int) $tenantId]);
        }
    }
}
