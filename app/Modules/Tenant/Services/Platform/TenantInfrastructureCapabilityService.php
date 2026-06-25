<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Platform;

use Modules\Tenant\Constants\TenantDatabaseStrategy;
use Modules\Tenant\Constants\TenantInfrastructureStrategy;

final class TenantInfrastructureCapabilityService
{
    /** @return array<string, mixed> */
    public function inspect(): array
    {
        return [
            'database' => [
                'strategy' => (string) config('tenant.infrastructure.database_strategy', TenantDatabaseStrategy::SHARED_SCHEMA),
                'tenant_specific_profiles_supported' => false,
            ],
            'storage' => [
                'strategy' => TenantInfrastructureStrategy::SHARED_PRIVATE_STORAGE,
                'isolation' => TenantInfrastructureStrategy::TENANT_OBJECT_KEY_PREFIX,
                'disk' => (string) config('tenant.documents.disk', 'tenant_private'),
                'tenant_specific_profiles_supported' => false,
            ],
            'mail' => [
                'strategy' => TenantInfrastructureStrategy::PLATFORM_MAILER,
                'tenant_specific_profiles_supported' => false,
            ],
            'configuration' => [
                'precedence' => ['organization_unit', 'tenant', 'global', 'definition_default'],
                'arbitrary_laravel_config_overrides_supported' => false,
            ],
        ];
    }
}
