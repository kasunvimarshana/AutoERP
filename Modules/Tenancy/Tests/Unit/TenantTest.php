<?php

declare(strict_types=1);

namespace Modules\Tenancy\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tenancy\Entities\Tenant;
use Tests\TestCase;

class TenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_tenant_successfully(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Test Company',
            'slug' => 'test-company',
            'domain' => 'test-company.example.com',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'Test Company',
            'slug' => 'test-company',
            'domain' => 'test-company.example.com',
            'status' => 'active',
        ]);
    }

    public function test_it_casts_settings_to_array(): void
    {
        $settings = [
            'timezone' => 'UTC',
            'locale' => 'en',
            'currency' => 'USD',
        ];

        $tenant = Tenant::factory()->create([
            'settings' => $settings,
        ]);

        $this->assertIsArray($tenant->settings);
        $this->assertEquals('UTC', $tenant->settings['timezone']);
        $this->assertEquals('en', $tenant->settings['locale']);
        $this->assertEquals('USD', $tenant->settings['currency']);
    }

    public function test_it_casts_features_to_array(): void
    {
        $features = ['sales', 'crm', 'inventory', 'accounting'];

        $tenant = Tenant::factory()->create([
            'features' => $features,
        ]);

        $this->assertIsArray($tenant->features);
        $this->assertContains('sales', $tenant->features);
        $this->assertContains('crm', $tenant->features);
        $this->assertContains('inventory', $tenant->features);
        $this->assertContains('accounting', $tenant->features);
    }

    public function test_it_casts_limits_to_array(): void
    {
        $limits = [
            'users' => 10,
            'storage_mb' => 5120,
            'api_calls_per_hour' => 1000,
        ];

        $tenant = Tenant::factory()->create([
            'limits' => $limits,
        ]);

        $this->assertIsArray($tenant->limits);
        $this->assertEquals(10, $tenant->limits['users']);
        $this->assertEquals(5120, $tenant->limits['storage_mb']);
        $this->assertEquals(1000, $tenant->limits['api_calls_per_hour']);
    }

    public function test_it_checks_if_tenant_is_active(): void
    {
        $activeTenant = Tenant::factory()->create(['status' => 'active']);
        $inactiveTenant = Tenant::factory()->create(['status' => 'inactive']);

        $this->assertTrue($activeTenant->isActive());
        $this->assertFalse($inactiveTenant->isActive());
    }

    public function test_it_checks_if_tenant_is_on_trial(): void
    {
        $trialTenant = Tenant::factory()->onTrial()->create();
        $regularTenant = Tenant::factory()->create(['trial_ends_at' => null]);

        $this->assertTrue($trialTenant->onTrial());
        $this->assertFalse($regularTenant->onTrial());
    }

    public function test_it_checks_if_trial_has_expired(): void
    {
        $tenant = Tenant::factory()->create([
            'trial_ends_at' => now()->subDays(1),
        ]);

        $this->assertFalse($tenant->onTrial());
    }

    public function test_it_checks_if_subscription_is_active(): void
    {
        $activeSubscription = Tenant::factory()->create([
            'subscription_ends_at' => now()->addYear(),
        ]);
        $expiredSubscription = Tenant::factory()->expired()->create();

        $this->assertTrue($activeSubscription->hasActiveSubscription());
        $this->assertFalse($expiredSubscription->hasActiveSubscription());
    }

    public function test_it_activates_tenant(): void
    {
        $tenant = Tenant::factory()->suspended()->create();

        $this->assertEquals('suspended', $tenant->status);

        $tenant->activate();

        $this->assertEquals('active', $tenant->fresh()->status);
        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'status' => 'active',
        ]);
    }

    public function test_it_suspends_tenant(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'active']);

        $tenant->suspend();

        $this->assertEquals('suspended', $tenant->fresh()->status);
        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'status' => 'suspended',
        ]);
    }

    public function test_it_gets_setting_value(): void
    {
        $tenant = Tenant::factory()->create([
            'settings' => [
                'timezone' => 'America/New_York',
                'locale' => 'en',
                'company' => [
                    'name' => 'Test Company',
                    'email' => 'info@testcompany.com',
                ],
            ],
        ]);

        $this->assertEquals('America/New_York', $tenant->getSetting('timezone'));
        $this->assertEquals('en', $tenant->getSetting('locale'));
        $this->assertEquals('Test Company', $tenant->getSetting('company.name'));
        $this->assertEquals('info@testcompany.com', $tenant->getSetting('company.email'));
    }

    public function test_it_gets_setting_with_default_value(): void
    {
        $tenant = Tenant::factory()->create([
            'settings' => [],
        ]);

        $this->assertEquals('UTC', $tenant->getSetting('timezone', 'UTC'));
        $this->assertNull($tenant->getSetting('nonexistent'));
    }

    public function test_it_sets_setting_value(): void
    {
        $tenant = Tenant::factory()->create([
            'settings' => ['timezone' => 'UTC'],
        ]);

        $tenant->setSetting('timezone', 'America/Los_Angeles');

        $this->assertEquals('America/Los_Angeles', $tenant->fresh()->getSetting('timezone'));
    }

    public function test_it_sets_nested_setting_value(): void
    {
        $tenant = Tenant::factory()->create([
            'settings' => [],
        ]);

        $tenant->setSetting('company.name', 'New Company Name');
        $tenant->setSetting('company.email', 'new@company.com');

        $tenant = $tenant->fresh();
        $this->assertEquals('New Company Name', $tenant->getSetting('company.name'));
        $this->assertEquals('new@company.com', $tenant->getSetting('company.email'));
    }

    public function test_it_casts_trial_ends_at_to_datetime(): void
    {
        $trialDate = now()->addDays(14);
        $tenant = Tenant::factory()->create([
            'trial_ends_at' => $trialDate,
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $tenant->trial_ends_at);
        $this->assertEquals($trialDate->format('Y-m-d'), $tenant->trial_ends_at->format('Y-m-d'));
    }

    public function test_it_casts_subscription_ends_at_to_datetime(): void
    {
        $subscriptionDate = now()->addYear();
        $tenant = Tenant::factory()->create([
            'subscription_ends_at' => $subscriptionDate,
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $tenant->subscription_ends_at);
        $this->assertEquals($subscriptionDate->format('Y-m-d'), $tenant->subscription_ends_at->format('Y-m-d'));
    }

    public function test_it_has_unique_slug(): void
    {
        Tenant::factory()->create(['slug' => 'unique-company']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Tenant::factory()->create(['slug' => 'unique-company']);
    }

    public function test_it_has_unique_domain(): void
    {
        Tenant::factory()->create(['domain' => 'unique.example.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Tenant::factory()->create(['domain' => 'unique.example.com']);
    }

    public function test_it_tracks_timestamps(): void
    {
        $tenant = Tenant::factory()->create();

        $this->assertNotNull($tenant->created_at);
        $this->assertNotNull($tenant->updated_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $tenant->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $tenant->updated_at);
    }

    public function test_it_handles_null_settings(): void
    {
        $tenant = Tenant::factory()->create(['settings' => null]);

        $this->assertNull($tenant->settings);
        $this->assertNull($tenant->getSetting('any.key'));
    }

    public function test_it_handles_null_features(): void
    {
        $tenant = Tenant::factory()->create(['features' => null]);

        $this->assertNull($tenant->features);
    }

    public function test_it_handles_null_limits(): void
    {
        $tenant = Tenant::factory()->create(['limits' => null]);

        $this->assertNull($tenant->limits);
    }

    public function test_small_business_factory_state(): void
    {
        $tenant = Tenant::factory()->smallBusiness()->create();

        $this->assertEquals(5, $tenant->limits['users']);
        $this->assertEquals(1024, $tenant->limits['storage_mb']);
        $this->assertEquals(1000, $tenant->limits['api_calls_per_hour']);
        $this->assertEquals(100, $tenant->limits['monthly_invoices']);
    }

    public function test_enterprise_factory_state(): void
    {
        $tenant = Tenant::factory()->enterprise()->create();

        $this->assertNull($tenant->limits['users']);
        $this->assertNull($tenant->limits['storage_mb']);
        $this->assertNull($tenant->limits['api_calls_per_hour']);
        $this->assertNull($tenant->limits['monthly_invoices']);
    }

    public function test_suspended_factory_state(): void
    {
        $tenant = Tenant::factory()->suspended()->create();

        $this->assertEquals('suspended', $tenant->status);
        $this->assertFalse($tenant->isActive());
    }

    public function test_inactive_factory_state(): void
    {
        $tenant = Tenant::factory()->inactive()->create();

        $this->assertEquals('inactive', $tenant->status);
        $this->assertFalse($tenant->isActive());
    }
}
