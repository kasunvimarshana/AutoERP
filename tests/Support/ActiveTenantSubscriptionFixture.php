<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use Modules\Tenant\Constants\TenantCurrentSubscriptionState;
use Modules\Tenant\Constants\TenantSubscriptionStatus;
use Modules\Tenant\Services\Plans\TenantModuleCatalogue;
use Modules\Tenant\Services\Plans\TenantPlanSchema;
use RuntimeException;

final class ActiveTenantSubscriptionFixture
{
    /**
     * Create the minimal active commercial contract required by tenant runtime tests.
     *
     * @param list<string>|null $enabledModules
     */
    public static function create(int $tenantId, ?array $enabledModules = null): int
    {
        if ($tenantId < 1) {
            throw new RuntimeException('Active tenant subscription fixture requires a valid tenant identifier.');
        }

        $now = now();
        $features = [
            'enabled_modules' => $enabledModules ?? app(TenantModuleCatalogue::class)->planControlledCodes(),
        ];
        $limits = [];

        $planId = (int) DB::table('tenant_plans')->insertGetId([
            'row_version' => 1,
            'name' => 'Test tenant plan '.$tenantId,
            'slug' => 'test-tenant-plan-'.$tenantId,
            'is_active' => true,
            'created_by' => null,
            'updated_by' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $revisionId = (int) DB::table('tenant_plan_revisions')->insertGetId([
            'tenant_plan_id' => $planId,
            'revision_number' => 1,
            'features_schema_version' => TenantPlanSchema::SCHEMA_VERSION,
            'features' => json_encode($features, JSON_THROW_ON_ERROR),
            'limits_schema_version' => TenantPlanSchema::SCHEMA_VERSION,
            'limits' => json_encode($limits, JSON_THROW_ON_ERROR),
            'price' => '0.000000',
            'currency_id' => null,
            'billing_interval' => 'month',
            'effective_at' => $now,
            'change_note' => 'Integration-test tenant runtime contract.',
            'created_by' => null,
            'created_at' => $now,
        ]);

        $subscriptionId = (int) DB::table('tenant_subscriptions')->insertGetId([
            'tenant_id' => $tenantId,
            'revision_number' => 1,
            'operation' => 'assign',
            'tenant_plan_revision_id' => $revisionId,
            'supersedes_subscription_id' => null,
            'contract_status' => TenantSubscriptionStatus::ACTIVE,
            'starts_at' => $now->copy()->subMinute(),
            'trial_ends_at' => null,
            'ends_at' => null,
            'change_reason' => 'Integration-test tenant runtime contract.',
            'plan_name' => 'Test tenant plan '.$tenantId,
            'plan_slug' => 'test-tenant-plan-'.$tenantId,
            'plan_features_schema_version' => TenantPlanSchema::SCHEMA_VERSION,
            'plan_features' => json_encode($features, JSON_THROW_ON_ERROR),
            'plan_limits_schema_version' => TenantPlanSchema::SCHEMA_VERSION,
            'plan_limits' => json_encode($limits, JSON_THROW_ON_ERROR),
            'price' => '0.000000',
            'currency_code' => 'USD',
            'currency_symbol' => '$',
            'billing_interval' => 'month',
            'created_by' => null,
            'created_by_type' => 'system',
            'created_by_name' => 'Integration test',
            'created_by_email' => null,
            'created_at' => $now,
        ]);

        DB::table('tenant_current_subscriptions')->insert([
            'tenant_id' => $tenantId,
            'tenant_subscription_id' => $subscriptionId,
            'state' => TenantCurrentSubscriptionState::ASSIGNED,
            'state_reason' => 'Integration-test tenant runtime contract.',
            'state_changed_at' => $now,
            'row_version' => 1,
            'assigned_at' => $now,
            'assigned_by' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $subscriptionId;
    }

    private function __construct() {}
}
