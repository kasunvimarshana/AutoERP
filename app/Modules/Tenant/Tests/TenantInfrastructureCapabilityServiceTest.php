<?php

declare(strict_types=1);

namespace Modules\Tenant\Tests;

use Modules\Tenant\Constants\TenantDatabaseStrategy;
use Modules\Tenant\Constants\TenantInfrastructureStrategy;
use Modules\Tenant\Services\Platform\TenantInfrastructureCapabilityService;
use Tests\TestCase;

final class TenantInfrastructureCapabilityServiceTest extends TestCase
{
    public function test_capabilities_report_only_the_runtime_strategies_this_release_supports(): void
    {
        config()->set('tenant.infrastructure.database_strategy', TenantDatabaseStrategy::SHARED_SCHEMA);
        config()->set('tenant.documents.disk', 'tenant_private');

        $capabilities = (new TenantInfrastructureCapabilityService())->inspect();

        self::assertSame(TenantDatabaseStrategy::SHARED_SCHEMA, $capabilities['database']['strategy']);
        self::assertFalse($capabilities['database']['tenant_specific_profiles_supported']);
        self::assertSame(TenantInfrastructureStrategy::SHARED_PRIVATE_STORAGE, $capabilities['storage']['strategy']);
        self::assertSame(TenantInfrastructureStrategy::TENANT_OBJECT_KEY_PREFIX, $capabilities['storage']['isolation']);
        self::assertFalse($capabilities['storage']['tenant_specific_profiles_supported']);
        self::assertSame(TenantInfrastructureStrategy::PLATFORM_MAILER, $capabilities['mail']['strategy']);
        self::assertFalse($capabilities['mail']['tenant_specific_profiles_supported']);
        self::assertSame(
            ['organization_unit', 'tenant', 'global', 'definition_default'],
            $capabilities['configuration']['precedence'],
        );
        self::assertFalse($capabilities['configuration']['arbitrary_laravel_config_overrides_supported']);
    }

    public function test_platform_operational_health_reports_pending_migration_state(): void
    {
        $source = file_get_contents(__DIR__.'/../Services/Platform/PlatformOperationalInfrastructureHealthService.php');
        self::assertIsString($source);

        self::assertStringContainsString('Migrator $migrator', $source);
        self::assertStringContainsString('$migrationStatus = $this->migrationStatus();', $source);
        self::assertStringContainsString("'ready' => $"."mailReady && $"."queueReady && (bool) $"."migrationStatus['ready']", $source);
        self::assertStringContainsString("'migrations' => $"."migrationStatus", $source);
        self::assertStringContainsString('getMigrationFiles', $source);
        self::assertStringContainsString('getRepository()->getRan()', $source);
        self::assertStringContainsString("'pending_count' => count($"."pending)", $source);
        self::assertStringContainsString('NON_PRODUCTION_MAILERS', $source);
        self::assertStringContainsString('QUEUE_SYNC', $source);
        self::assertStringContainsString('QUEUE_DATABASE', $source);
        self::assertStringContainsString('JOBS_TABLE', $source);
        self::assertStringContainsString('FAILED_JOBS_TABLE', $source);
    }
}
