<?php

declare(strict_types=1);

namespace Modules\Configuration\Services;

use InvalidArgumentException;
use Illuminate\Validation\ValidationException;
use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Configuration\Data\ConfigurationScopeContext;
use Modules\Core\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\OrganizationUnit\Contracts\OrganizationUnitOwnershipCheckerInterface;

final class ConfigurationScopeResolver
{
    public function __construct(
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
        private readonly OrganizationUnitOwnershipCheckerInterface $organizationUnits,
    ) {}

    public function current(string $scope): ConfigurationScopeContext
    {
        return match ($scope) {
            ConfigurationScope::GLOBAL => new ConfigurationScopeContext(
                ConfigurationScope::GLOBAL,
                null,
                null,
            ),
            ConfigurationScope::TENANT => new ConfigurationScopeContext(
                ConfigurationScope::TENANT,
                $this->currentTenant->requireCurrent()->tenantId(),
                null,
            ),
            ConfigurationScope::ORGANIZATION_UNIT => $this->currentOrganizationContext(),
            default => throw ValidationException::withMessages([
                'scope' => ['Select a valid configuration scope.'],
            ]),
        };
    }

    public function explicit(
        string $scope,
        ?int $tenantId,
        ?int $organizationUnitId,
    ): ConfigurationScopeContext {
        if ($scope === ConfigurationScope::GLOBAL) {
            return new ConfigurationScopeContext($scope, null, null);
        }
        if ($tenantId === null || $tenantId < 1) {
            throw new InvalidArgumentException('A valid tenant ID is required.');
        }
        if ($scope === ConfigurationScope::TENANT) {
            return new ConfigurationScopeContext($scope, $tenantId, null);
        }
        if (
            $scope !== ConfigurationScope::ORGANIZATION_UNIT
            || $organizationUnitId === null
            || $organizationUnitId < 1
        ) {
            throw new InvalidArgumentException('A valid organization-unit ID is required.');
        }
        if (! $this->organizationUnits->belongsToActiveTenant(
            $organizationUnitId,
            $tenantId,
        )) {
            throw new InvalidArgumentException(
                'The organization unit does not belong to the tenant.',
            );
        }

        return new ConfigurationScopeContext($scope, $tenantId, $organizationUnitId);
    }

    private function currentOrganizationContext(): ConfigurationScopeContext
    {
        $tenantId = $this->currentTenant->requireCurrent()->tenantId();
        $organization = $this->currentOrganizationUnit->current();

        if ($organization === null) {
            throw ValidationException::withMessages([
                'scope' => [
                    'Select an organization unit before managing organization-specific settings.',
                ],
            ]);
        }
        if ($organization->tenantId() !== $tenantId) {
            throw ValidationException::withMessages([
                'scope' => ['The selected organization unit is outside the active tenant.'],
            ]);
        }

        return new ConfigurationScopeContext(
            ConfigurationScope::ORGANIZATION_UNIT,
            $tenantId,
            $organization->organizationUnitId(),
        );
    }
}
