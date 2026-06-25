<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use Modules\Core\DTOs\DataRecord;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Data\TenantReadiness;
use Modules\Tenant\Repositories\TenantDomainRepositoryInterface;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Services\Contracts\TenantOrganizationUnitGatewayInterface;

final class TenantReadinessService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantDomainRepositoryInterface $domains,
        private readonly TenantOrganizationUnitGatewayInterface $organizationUnits,
        private readonly TenantSubscriptionWindowPolicy $subscriptionWindow,
    ) {}

    public function get(int|string $tenantId): Result
    {
        $tenant = $this->tenants->findById($tenantId);
        if ($tenant === null) {
            return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant not found.'));
        }

        return Result::success($this->evaluate($tenant));
    }

    public function evaluate(DataRecord $tenant): TenantReadiness
    {
        $tenantId = (int) $tenant->id();
        $plan = $tenant->get('plan');
        $currency = $tenant->get('base_currency');
        $root = $this->organizationUnits->findRoot($tenantId);
        $primaryDomain = $this->domains->findPrimaryByTenant($tenantId);
        $subscriptionValid = $this->subscriptionWindow->isValid($tenant);

        return new TenantReadiness([
            $this->check(
                'subscription_plan',
                'Active subscription plan',
                $tenant->get('tenant_plan_id') !== null
                    && is_array($plan)
                    && (bool) ($plan['is_active'] ?? false),
                'Assign an active tenant plan.',
            ),
            $this->check(
                'base_currency',
                'Active base currency',
                $tenant->get('base_currency_id') !== null
                    && is_array($currency)
                    && (bool) ($currency['is_active'] ?? false),
                'Assign an active base accounting currency.',
            ),
            $this->check(
                'root_organization_unit',
                'Active root organization unit',
                $root !== null
                    && $root->get('parent_id') === null
                    && (bool) $root->get('is_active', false)
                    && trim((string) $root->get('path', '')) !== '',
                'Create or activate the tenant root organization unit.',
            ),
            $this->check(
                'primary_domain',
                'Verified primary domain',
                $primaryDomain !== null,
                'Verify and select a primary tenant domain.',
            ),
            $this->check(
                'subscription_window',
                'Valid trial or subscription period',
                $subscriptionValid,
                'Extend the trial or subscription period before activation.',
            ),
        ]);
    }

    /** @return array{key:string,label:string,ready:bool,guidance:string} */
    private function check(string $key, string $label, bool $ready, string $guidance): array
    {
        return compact('key', 'label', 'ready', 'guidance');
    }

}
