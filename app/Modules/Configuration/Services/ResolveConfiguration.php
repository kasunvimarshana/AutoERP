<?php

declare(strict_types=1);

namespace Modules\Configuration\Services;

use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Configuration\Contracts\ConfigurationDefinitionRegistryInterface;
use Modules\Configuration\Contracts\ConfigurationResolverInterface;
use Modules\Configuration\Contracts\ConfigurationValueRepositoryInterface;
use Modules\Configuration\Data\ConfigurationDefinition;
use Modules\Configuration\Data\ConfigurationScopeContext;
use Modules\Configuration\Data\ResolvedConfigurationValue;
use Modules\Configuration\Data\StoredConfigurationValue;
use Modules\OrganizationUnit\Contracts\OrganizationUnitHierarchyReaderInterface;
use RuntimeException;

final class ResolveConfiguration implements ConfigurationResolverInterface
{
    /** @var array<string, StoredConfigurationValue|null> */
    private array $requestCache = [];

    public function __construct(
        private readonly ConfigurationDefinitionRegistryInterface $definitions,
        private readonly ConfigurationValueRepositoryInterface $repository,
        private readonly ConfigurationScopeResolver $scopes,
        private readonly ConfigurationValueCodec $codec,
        private readonly ConfigurationValueValidator $validator,
        private readonly OrganizationUnitHierarchyReaderInterface $organizationHierarchy,
    ) {}

    public function resolve(
        string $key,
        int $tenantId,
        ?int $organizationUnitId = null,
    ): ResolvedConfigurationValue {
        $definition = $this->definitions->get($key);

        if (
            $organizationUnitId !== null
            && in_array(ConfigurationScope::ORGANIZATION_UNIT, $definition->allowedScopes, true)
        ) {
            $stored = $this->organizationValue($definition, $tenantId, $organizationUnitId, true);
            if ($stored !== null) {
                return $this->resolved($definition, $stored);
            }
        }

        return $this->resolveTenantGlobalOrDefault($definition, $tenantId);
    }

    public function value(
        string $key,
        int $tenantId,
        ?int $organizationUnitId = null,
    ): mixed {
        return $this->resolve($key, $tenantId, $organizationUnitId)->value;
    }

    public function resolveBelow(
        ConfigurationScopeContext $context,
        string $key,
    ): ResolvedConfigurationValue {
        $definition = $this->definitions->get($key);

        if (
            $context->scope === ConfigurationScope::ORGANIZATION_UNIT
            && $context->tenantId !== null
            && $context->organizationUnitId !== null
            && $definition->inheritOrganizationHierarchy
        ) {
            $stored = $this->organizationValue(
                $definition,
                $context->tenantId,
                $context->organizationUnitId,
                false,
            );
            if ($stored !== null) {
                return $this->resolved($definition, $stored);
            }
        }

        if ($context->scope === ConfigurationScope::ORGANIZATION_UNIT && $context->tenantId !== null) {
            return $this->resolveTenantGlobalOrDefault($definition, $context->tenantId);
        }

        if (
            $context->scope === ConfigurationScope::TENANT
            && in_array(ConfigurationScope::GLOBAL, $definition->allowedScopes, true)
        ) {
            $stored = $this->exact(
                $this->scopes->explicit(ConfigurationScope::GLOBAL, null, null),
                $definition->key,
            );
            if ($stored !== null) {
                return $this->resolved($definition, $stored);
            }
        }

        return $this->defaultValue($definition);
    }

    public function exact(
        ConfigurationScopeContext $context,
        string $key,
    ): ?StoredConfigurationValue {
        $cacheKey = $this->cacheKey($context, $key);

        if (array_key_exists($cacheKey, $this->requestCache)) {
            return $this->requestCache[$cacheKey];
        }

        return $this->requestCache[$cacheKey] = $this->repository->findExact($context, $key);
    }

    public function forget(ConfigurationScopeContext $context, string $key): void
    {
        unset($this->requestCache[$this->cacheKey($context, $key)]);
    }

    private function organizationValue(
        ConfigurationDefinition $definition,
        int $tenantId,
        int $organizationUnitId,
        bool $includeCurrent,
    ): ?StoredConfigurationValue {
        $organizationUnitIds = $includeCurrent ? [$organizationUnitId] : [];

        if ($definition->inheritOrganizationHierarchy) {
            array_push(
                $organizationUnitIds,
                ...$this->organizationHierarchy->activeAncestorIds($tenantId, $organizationUnitId),
            );
        }

        foreach ($organizationUnitIds as $candidateId) {
            $stored = $this->exact(
                $this->scopes->explicit(
                    ConfigurationScope::ORGANIZATION_UNIT,
                    $tenantId,
                    $candidateId,
                ),
                $definition->key,
            );
            if ($stored !== null) {
                return $stored;
            }
        }

        return null;
    }

    private function resolveTenantGlobalOrDefault(
        ConfigurationDefinition $definition,
        int $tenantId,
    ): ResolvedConfigurationValue {
        if (in_array(ConfigurationScope::TENANT, $definition->allowedScopes, true)) {
            $stored = $this->exact(
                $this->scopes->explicit(ConfigurationScope::TENANT, $tenantId, null),
                $definition->key,
            );
            if ($stored !== null) {
                return $this->resolved($definition, $stored);
            }
        }

        if (in_array(ConfigurationScope::GLOBAL, $definition->allowedScopes, true)) {
            $stored = $this->exact(
                $this->scopes->explicit(ConfigurationScope::GLOBAL, null, null),
                $definition->key,
            );
            if ($stored !== null) {
                return $this->resolved($definition, $stored);
            }
        }

        return $this->defaultValue($definition);
    }

    private function defaultValue(ConfigurationDefinition $definition): ResolvedConfigurationValue
    {
        return new ResolvedConfigurationValue(
            definition: $definition,
            value: $this->validator->validate($definition, $definition->defaultValue),
            sourceScope: 'default',
            tenantId: null,
            organizationUnitId: null,
            rowVersion: null,
            usesDefault: true,
        );
    }

    private function resolved(
        ConfigurationDefinition $definition,
        StoredConfigurationValue $stored,
    ): ResolvedConfigurationValue {
        if (
            $stored->definitionVersion !== $definition->version
            || $stored->valueType !== $definition->valueType
            || $stored->sensitive !== $definition->sensitive
        ) {
            throw new RuntimeException(
                "Stored configuration metadata for [{$definition->key}] does not match its definition.",
            );
        }

        return new ResolvedConfigurationValue(
            definition: $definition,
            value: $this->codec->decode($definition, $stored->storedValue),
            sourceScope: $stored->scope,
            tenantId: $stored->tenantId,
            organizationUnitId: $stored->organizationUnitId,
            rowVersion: $stored->rowVersion,
            usesDefault: false,
        );
    }

    private function cacheKey(ConfigurationScopeContext $context, string $key): string
    {
        return implode(':', [
            $context->scope,
            $context->tenantId ?? 'none',
            $context->organizationUnitId ?? 'none',
            strtolower(trim($key)),
        ]);
    }
}
