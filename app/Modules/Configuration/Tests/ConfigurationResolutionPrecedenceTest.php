<?php

declare(strict_types=1);

namespace Modules\Configuration\Tests;

use DateTimeImmutable;
use Illuminate\Contracts\Encryption\Encrypter;
use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Configuration\Constants\ConfigurationValueType;
use Modules\Configuration\Contracts\ConfigurationDefinitionRegistryInterface;
use Modules\Configuration\Contracts\ConfigurationValueRepositoryInterface;
use Modules\Configuration\Data\ConfigurationDefinition;
use Modules\Configuration\Data\ConfigurationScopeContext;
use Modules\Configuration\Data\StoredConfigurationValue;
use Modules\Configuration\Services\ConfigurationScopeResolver;
use Modules\Configuration\Services\ConfigurationValueCodec;
use Modules\Configuration\Services\ConfigurationValueValidator;
use Modules\Configuration\Services\ResolveConfiguration;
use Modules\Core\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\OrganizationUnit\Contracts\OrganizationUnitOwnershipCheckerInterface;
use Modules\ReferenceData\Contracts\ReferenceValueLookupInterface;
use PHPUnit\Framework\TestCase;

final class ConfigurationResolutionPrecedenceTest extends TestCase
{
    public function test_organization_override_wins_over_tenant_and_global_values(): void
    {
        $resolved = $this->resolver([
            ConfigurationScope::ORGANIZATION_UNIT => 'organization',
            ConfigurationScope::TENANT => 'tenant',
            ConfigurationScope::GLOBAL => 'global',
        ])->resolve('test.setting', 10, 20);

        self::assertSame('organization', $resolved->value);
        self::assertSame(ConfigurationScope::ORGANIZATION_UNIT, $resolved->sourceScope);
        self::assertSame(10, $resolved->tenantId);
        self::assertSame(20, $resolved->organizationUnitId);
    }

    public function test_tenant_override_wins_when_organization_override_is_absent(): void
    {
        $resolved = $this->resolver([
            ConfigurationScope::TENANT => 'tenant',
            ConfigurationScope::GLOBAL => 'global',
        ])->resolve('test.setting', 10, 20);

        self::assertSame('tenant', $resolved->value);
        self::assertSame(ConfigurationScope::TENANT, $resolved->sourceScope);
        self::assertSame(10, $resolved->tenantId);
        self::assertNull($resolved->organizationUnitId);
    }

    public function test_global_override_wins_when_tenant_specific_values_are_absent(): void
    {
        $resolved = $this->resolver([
            ConfigurationScope::GLOBAL => 'global',
        ])->resolve('test.setting', 10, 20);

        self::assertSame('global', $resolved->value);
        self::assertSame(ConfigurationScope::GLOBAL, $resolved->sourceScope);
        self::assertNull($resolved->tenantId);
        self::assertNull($resolved->organizationUnitId);
    }

    public function test_definition_default_is_used_when_no_override_exists(): void
    {
        $resolved = $this->resolver([])->resolve('test.setting', 10, 20);

        self::assertSame('default', $resolved->value);
        self::assertSame('default', $resolved->sourceScope);
        self::assertTrue($resolved->usesDefault);
    }

    /** @param array<string,string> $valuesByScope */
    private function resolver(array $valuesByScope): ResolveConfiguration
    {
        $definition = new ConfigurationDefinition(
            key: 'test.setting',
            label: 'Test setting',
            description: 'Verifies configuration inheritance.',
            owner: 'Tests',
            valueType: ConfigurationValueType::STRING,
            allowedScopes: [
                ConfigurationScope::ORGANIZATION_UNIT,
                ConfigurationScope::TENANT,
                ConfigurationScope::GLOBAL,
            ],
            defaultValue: 'default',
            nullable: false,
            sensitive: false,
            runtimeMutable: true,
        );

        $definitions = $this->createMock(ConfigurationDefinitionRegistryInterface::class);
        $definitions->method('get')->with('test.setting')->willReturn($definition);

        $repository = $this->createMock(ConfigurationValueRepositoryInterface::class);
        $repository->method('findExact')->willReturnCallback(
            static function (ConfigurationScopeContext $context, string $key) use ($valuesByScope): ?StoredConfigurationValue {
                $value = $valuesByScope[$context->scope] ?? null;
                if ($value === null) {
                    return null;
                }

                return new StoredConfigurationValue(
                    id: match ($context->scope) {
                        ConfigurationScope::ORGANIZATION_UNIT => 3,
                        ConfigurationScope::TENANT => 2,
                        default => 1,
                    },
                    scope: $context->scope,
                    tenantId: $context->tenantId,
                    organizationUnitId: $context->organizationUnitId,
                    key: $key,
                    definitionVersion: 1,
                    storedValue: json_encode($value, JSON_THROW_ON_ERROR),
                    valueType: ConfigurationValueType::STRING,
                    sensitive: false,
                    rowVersion: 1,
                    updatedAt: new DateTimeImmutable('2026-06-23T00:00:00+00:00'),
                );
            },
        );

        $currentTenant = $this->createMock(CurrentTenantContextAccessorInterface::class);
        $currentOrganization = $this->createMock(CurrentOrganizationUnitContextAccessorInterface::class);
        $ownership = $this->createMock(OrganizationUnitOwnershipCheckerInterface::class);
        $ownership->method('belongsToActiveTenant')->willReturn(true);
        $scopes = new ConfigurationScopeResolver($currentTenant, $currentOrganization, $ownership);

        $encrypter = $this->createMock(Encrypter::class);
        $codec = new ConfigurationValueCodec($encrypter);
        $lookup = $this->createMock(ReferenceValueLookupInterface::class);
        $validator = new ConfigurationValueValidator($lookup);

        return new ResolveConfiguration($definitions, $repository, $scopes, $codec, $validator);
    }
}
