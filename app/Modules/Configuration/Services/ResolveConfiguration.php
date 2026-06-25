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
            $stored = $this->exact(
                $this->scopes->explicit(
                    ConfigurationScope::ORGANIZATION_UNIT,
                    $tenantId,
                    $organizationUnitId,
                ),
                $definition->key,
            );

            if ($stored !== null) {
                return $this->resolved($definition, $stored);
            }
        }

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
            && in_array(ConfigurationScope::TENANT, $definition->allowedScopes, true)
        ) {
            $stored = $this->exact(
                $this->scopes->explicit(ConfigurationScope::TENANT, $context->tenantId, null),
                $definition->key,
            );
            if ($stored !== null) {
                return $this->resolved($definition, $stored);
            }
        }

        if (
            $context->scope !== ConfigurationScope::GLOBAL
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

    private function resolved(
        ConfigurationDefinition $definition,
        StoredConfigurationValue $stored,
    ): ResolvedConfigurationValue {
        if (
            $stored->valueType !== $definition->valueType
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
