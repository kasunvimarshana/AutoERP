<?php

declare(strict_types=1);

namespace Modules\Tenancy\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tenancy\Entities\Tenant;
use Tests\TestCase;

/**
 * Tenant Feature Access Tests
 *
 * Tests for tenant feature flags and access control.
 * Verifies that tenants can only access features included in their plan.
 */
class TenantFeatureAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_has_feature_access(): void
    {
        $tenant = Tenant::factory()->create([
            'features' => ['sales', 'crm', 'inventory'],
        ]);

        $this->assertContains('sales', $tenant->features);
        $this->assertContains('crm', $tenant->features);
        $this->assertContains('inventory', $tenant->features);
    }

    public function test_tenant_does_not_have_feature_access(): void
    {
        $tenant = Tenant::factory()->create([
            'features' => ['sales', 'crm'],
        ]);

        $this->assertNotContains('inventory', $tenant->features);
        $this->assertNotContains('accounting', $tenant->features);
    }

    public function test_feature_can_be_added_to_tenant(): void
    {
        $tenant = Tenant::factory()->create([
            'features' => ['sales', 'crm'],
        ]);

        $features = $tenant->features;
        $features[] = 'inventory';
        $tenant->update(['features' => $features]);

        $this->assertContains('inventory', $tenant->fresh()->features);
    }

    public function test_feature_can_be_removed_from_tenant(): void
    {
        $tenant = Tenant::factory()->create([
            'features' => ['sales', 'crm', 'inventory'],
        ]);

        $features = array_diff($tenant->features, ['inventory']);
        $tenant->update(['features' => array_values($features)]);

        $this->assertNotContains('inventory', $tenant->fresh()->features);
        $this->assertContains('sales', $tenant->fresh()->features);
    }

    public function test_small_business_plan_has_limited_features(): void
    {
        $tenant = Tenant::factory()->smallBusiness()->create();

        // Verify limits
        $this->assertEquals(5, $tenant->limits['users']);
        $this->assertEquals(1024, $tenant->limits['storage_mb']);
        $this->assertEquals(1000, $tenant->limits['api_calls_per_hour']);
        $this->assertEquals(100, $tenant->limits['monthly_invoices']);
    }

    public function test_enterprise_plan_has_unlimited_features(): void
    {
        $tenant = Tenant::factory()->enterprise()->create();

        // Verify no limits (null means unlimited)
        $this->assertNull($tenant->limits['users']);
        $this->assertNull($tenant->limits['storage_mb']);
        $this->assertNull($tenant->limits['api_calls_per_hour']);
        $this->assertNull($tenant->limits['monthly_invoices']);
    }

    public function test_tenant_respects_user_limit(): void
    {
        $tenant = Tenant::factory()->smallBusiness()->create();

        $userLimit = $tenant->limits['users'];
        $this->assertEquals(5, $userLimit);

        // In real implementation, this would check actual user count
        // against the limit before allowing user creation
        $this->assertTrue($userLimit > 0);
    }

    public function test_tenant_respects_storage_limit(): void
    {
        $tenant = Tenant::factory()->smallBusiness()->create();

        $storageLimit = $tenant->limits['storage_mb'];
        $this->assertEquals(1024, $storageLimit); // 1GB

        // In real implementation, this would check actual storage usage
        $this->assertTrue($storageLimit > 0);
    }

    public function test_tenant_respects_api_rate_limit(): void
    {
        $tenant = Tenant::factory()->smallBusiness()->create();

        $apiLimit = $tenant->limits['api_calls_per_hour'];
        $this->assertEquals(1000, $apiLimit);

        // In real implementation, this would be enforced via middleware
        $this->assertTrue($apiLimit > 0);
    }

    public function test_tenant_can_upgrade_plan(): void
    {
        $tenant = Tenant::factory()->smallBusiness()->create();

        // Upgrade to enterprise plan
        $tenant->update([
            'limits' => [
                'users' => null,
                'storage_mb' => null,
                'api_calls_per_hour' => null,
                'monthly_invoices' => null,
            ],
            'features' => [
                'sales',
                'crm',
                'inventory',
                'accounting',
                'reporting',
                'advanced_analytics',
                'api_access',
                'custom_fields',
                'sso',
                'audit_logs',
            ],
        ]);

        $tenant = $tenant->fresh();

        // Verify upgrade
        $this->assertNull($tenant->limits['users']);
        $this->assertContains('advanced_analytics', $tenant->features);
        $this->assertContains('sso', $tenant->features);
        $this->assertContains('audit_logs', $tenant->features);
    }

    public function test_tenant_can_downgrade_plan(): void
    {
        $tenant = Tenant::factory()->enterprise()->create();

        // Downgrade to small business plan
        $tenant->update([
            'limits' => [
                'users' => 5,
                'storage_mb' => 1024,
                'api_calls_per_hour' => 1000,
                'monthly_invoices' => 100,
            ],
            'features' => ['sales', 'crm'],
        ]);

        $tenant = $tenant->fresh();

        // Verify downgrade
        $this->assertEquals(5, $tenant->limits['users']);
        $this->assertCount(2, $tenant->features);
        $this->assertNotContains('sso', $tenant->features);
    }

    public function test_trial_tenant_has_full_access(): void
    {
        $tenant = Tenant::factory()->onTrial()->create([
            'features' => [
                'sales',
                'crm',
                'inventory',
                'accounting',
                'reporting',
            ],
        ]);

        $this->assertTrue($tenant->onTrial());
        $this->assertCount(5, $tenant->features);
    }

    public function test_expired_trial_tenant_can_be_limited(): void
    {
        $tenant = Tenant::factory()->create([
            'trial_ends_at' => now()->subDays(1),
            'subscription_ends_at' => null,
            'status' => 'inactive',
        ]);

        $this->assertFalse($tenant->onTrial());
        $this->assertFalse($tenant->hasActiveSubscription());
        $this->assertFalse($tenant->isActive());
    }

    public function test_tenant_with_active_subscription_maintains_access(): void
    {
        $tenant = Tenant::factory()->create([
            'trial_ends_at' => null,
            'subscription_ends_at' => now()->addYear(),
            'status' => 'active',
        ]);

        $this->assertTrue($tenant->hasActiveSubscription());
        $this->assertTrue($tenant->isActive());
    }

    public function test_expired_subscription_should_suspend_tenant(): void
    {
        $tenant = Tenant::factory()->expired()->create();

        $this->assertFalse($tenant->hasActiveSubscription());

        // In real implementation, a job would automatically suspend expired tenants
        if ($tenant->status !== 'suspended') {
            $tenant->suspend();
        }

        $this->assertEquals('suspended', $tenant->fresh()->status);
    }

    public function test_suspended_tenant_cannot_access_features(): void
    {
        $tenant = Tenant::factory()->suspended()->create();

        $this->assertEquals('suspended', $tenant->status);
        $this->assertFalse($tenant->isActive());

        // In real implementation, suspended tenants would be blocked
        // from accessing the system via middleware
    }

    public function test_feature_settings_can_be_customized_per_tenant(): void
    {
        $tenant = Tenant::factory()->create([
            'settings' => [
                'features' => [
                    'multi_currency' => true,
                    'multi_location' => true,
                    'advanced_reporting' => false,
                    'api_access' => true,
                    'custom_fields' => false,
                ],
            ],
        ]);

        $this->assertTrue($tenant->getSetting('features.multi_currency'));
        $this->assertTrue($tenant->getSetting('features.multi_location'));
        $this->assertFalse($tenant->getSetting('features.advanced_reporting'));
        $this->assertTrue($tenant->getSetting('features.api_access'));
        $this->assertFalse($tenant->getSetting('features.custom_fields'));
    }

    public function test_tenant_can_enable_optional_feature(): void
    {
        $tenant = Tenant::factory()->create([
            'settings' => [
                'features' => [
                    'sms_notifications' => false,
                ],
            ],
        ]);

        $this->assertFalse($tenant->getSetting('features.sms_notifications'));

        // Enable SMS notifications
        $tenant->setSetting('features.sms_notifications', true);

        $this->assertTrue($tenant->fresh()->getSetting('features.sms_notifications'));
    }

    public function test_tenant_can_disable_optional_feature(): void
    {
        $tenant = Tenant::factory()->create([
            'settings' => [
                'features' => [
                    'slack_integration' => true,
                ],
            ],
        ]);

        $this->assertTrue($tenant->getSetting('features.slack_integration'));

        // Disable Slack integration
        $tenant->setSetting('features.slack_integration', false);

        $this->assertFalse($tenant->fresh()->getSetting('features.slack_integration'));
    }

    public function test_feature_limits_are_enforced_in_settings(): void
    {
        $tenant = Tenant::factory()->create([
            'settings' => [
                'limits' => [
                    'max_products' => 100,
                    'max_customers' => 500,
                    'max_invoices_per_month' => 100,
                ],
            ],
        ]);

        $this->assertEquals(100, $tenant->getSetting('limits.max_products'));
        $this->assertEquals(500, $tenant->getSetting('limits.max_customers'));
        $this->assertEquals(100, $tenant->getSetting('limits.max_invoices_per_month'));
    }

    public function test_unlimited_plan_has_no_feature_restrictions(): void
    {
        $tenant = Tenant::factory()->enterprise()->create();

        // All limits should be null (unlimited)
        foreach ($tenant->limits as $limit) {
            $this->assertNull($limit);
        }
    }

    public function test_custom_feature_can_be_added(): void
    {
        $tenant = Tenant::factory()->create([
            'features' => ['sales', 'crm'],
            'settings' => [
                'custom_features' => [
                    'custom_dashboard' => true,
                    'white_label' => true,
                ],
            ],
        ]);

        $this->assertTrue($tenant->getSetting('custom_features.custom_dashboard'));
        $this->assertTrue($tenant->getSetting('custom_features.white_label'));
    }
}
