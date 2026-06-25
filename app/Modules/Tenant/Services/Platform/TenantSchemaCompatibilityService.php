<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Platform;

use Illuminate\Support\Facades\Schema;
use Throwable;

final class TenantSchemaCompatibilityService
{
    /** @var array<string, list<string>> */
    private const REQUIRED_SCHEMA = [
        'tenants' => ['id', 'row_version', 'status', 'base_currency_id'],
        'tenant_subscriptions' => ['id', 'tenant_id', 'revision_number', 'starts_at', 'ends_at'],
        'tenant_current_subscriptions' => ['tenant_id', 'tenant_subscription_id', 'state', 'row_version'],
        'tenant_subscription_events' => ['id', 'tenant_id', 'tenant_subscription_id', 'event_type'],
        'tenant_plan_revisions' => ['id', 'tenant_plan_id', 'features', 'limits', 'effective_at'],
        'tenant_onboarding_states' => [
            'tenant_id',
            'status',
            'row_version',
            'root_organization_unit_id',
            'super_admin_role_id',
            'invitation_id',
        ],
        'tenant_onboarding_steps' => ['tenant_id', 'step', 'status'],
        'organization_units' => ['id', 'tenant_id', 'parent_id', 'root_marker', 'path', 'depth'],
        'auth_registration_invitations' => ['id', 'tenant_id', 'status', 'accepted_by_user_id'],
    ];

    /** @return array{compatible:bool,missing_tables:list<string>,missing_columns:array<string,list<string>>} */
    public function inspect(): array
    {
        $missingTables = [];
        $missingColumns = [];

        try {
            foreach (self::REQUIRED_SCHEMA as $table => $columns) {
                if (! Schema::hasTable($table)) {
                    $missingTables[] = $table;
                    continue;
                }

                $missing = array_values(array_filter(
                    $columns,
                    static fn (string $column): bool => ! Schema::hasColumn($table, $column),
                ));
                if ($missing !== []) {
                    $missingColumns[$table] = $missing;
                }
            }
        } catch (Throwable) {
            return [
                'compatible' => false,
                'missing_tables' => array_keys(self::REQUIRED_SCHEMA),
                'missing_columns' => [],
            ];
        }

        return [
            'compatible' => $missingTables === [] && $missingColumns === [],
            'missing_tables' => $missingTables,
            'missing_columns' => $missingColumns,
        ];
    }
}
