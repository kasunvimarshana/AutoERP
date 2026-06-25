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

final class ConfigurationGlobalImpactService
{
    public function __construct(
        private readonly ConfigurationDefinitionRegistryInterface $definitions,
        private readonly ConfigurationValueRepositoryInterface $values,
        private readonly ConfigurationTargetPopulationInterface $targets,
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
        $overrideCount = min($tenantCount, $this->values->countTenantOverrides($definition->key));

        return new ConfigurationGlobalImpact(
            key: $definition->key,
            tenantCount: $tenantCount,
            tenantOverrideCount: $overrideCount,
            inheritingTenantCount: max(0, $tenantCount - $overrideCount),
        );
    }
}
