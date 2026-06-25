<?php

declare(strict_types=1);

namespace Modules\Configuration\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Configuration\Contracts\ConfigurationDefinitionRegistryInterface;
use Modules\Configuration\Contracts\ConfigurationTargetPopulationInterface;
use Modules\Configuration\Contracts\ConfigurationValueRepositoryInterface;
use Modules\Configuration\Data\ConfigurationGlobalImpact;
use Modules\OrganizationUnit\Contracts\OrganizationUnitPopulationReaderInterface;

final class ConfigurationGlobalImpactService
{
    public function __construct(
        private readonly ConfigurationDefinitionRegistryInterface $definitions,
        private readonly ConfigurationValueRepositoryInterface $values,
        private readonly ConfigurationTargetPopulationInterface $targets,
        private readonly OrganizationUnitPopulationReaderInterface $organizationUnits,
        private readonly ConfigurationAuthorizationService $authorization,
    ) {}

    public function forKey(string $key): ConfigurationGlobalImpact
    {
        if (! $this->authorization->canViewPlatformScope(ConfigurationScope::GLOBAL)) {
            throw new AuthorizationException('Viewing global configuration impact is not authorized.');
        }

        $definition = $this->definitions->get(strtolower(trim($key)));
        if (! in_array(ConfigurationScope::GLOBAL, $definition->allowedScopes, true)) {
            throw ValidationException::withMessages([
                'key' => ['This setting does not support a global default.'],
            ]);
        }

        $tenantCount = $this->targets->tenantCount();
        $tenantOverrideCount = min($tenantCount, $this->values->countTenantOverrides($definition->key));
        $supportsOrganizationUnits = in_array(
            ConfigurationScope::ORGANIZATION_UNIT,
            $definition->allowedScopes,
            true,
        );
        $organizationUnitCount = $supportsOrganizationUnits
            ? $this->organizationUnits->activeCount()
            : 0;
        $organizationUnitOverrideCount = $supportsOrganizationUnits
            ? min(
                $organizationUnitCount,
                $this->values->countOrganizationUnitOverrides($definition->key),
            )
            : 0;

        return new ConfigurationGlobalImpact(
            key: $definition->key,
            tenantCount: $tenantCount,
            tenantOverrideCount: $tenantOverrideCount,
            inheritingTenantCount: max(0, $tenantCount - $tenantOverrideCount),
            organizationUnitCount: $organizationUnitCount,
            organizationUnitOverrideCount: $organizationUnitOverrideCount,
            organizationUnitWithoutDirectOverrideCount: max(
                0,
                $organizationUnitCount - $organizationUnitOverrideCount,
            ),
        );
    }
}
