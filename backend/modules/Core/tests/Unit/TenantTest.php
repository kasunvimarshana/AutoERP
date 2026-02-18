<?php

namespace Modules\Core\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\Tenant;
use Tests\TestCase;

class TenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_be_created()
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'domain' => 'test.example.com',
            'status' => 'active',
        ]);

        $this->assertNotNull($tenant->uuid);
        $this->assertNotNull($tenant->database);
        $this->assertEquals('Test Tenant', $tenant->name);
        $this->assertEquals('test.example.com', $tenant->domain);
    }

    public function test_tenant_status_checks()
    {
        $activeTenant = Tenant::create([
            'name' => 'Active Tenant',
            'domain' => 'active.example.com',
            'status' => 'active',
        ]);

        $suspendedTenant = Tenant::create([
            'name' => 'Suspended Tenant',
            'domain' => 'suspended.example.com',
            'status' => 'suspended',
        ]);

        $this->assertTrue($activeTenant->isActive());
        $this->assertFalse($activeTenant->isSuspended());

        $this->assertFalse($suspendedTenant->isActive());
        $this->assertTrue($suspendedTenant->isSuspended());
    }

    public function test_tenant_settings()
    {
        $tenant = Tenant::create([
            'name' => 'Settings Tenant',
            'domain' => 'settings.example.com',
        ]);

        $tenant->setSetting('feature.enabled', true);
        $tenant->save();

        $this->assertTrue($tenant->getSetting('feature.enabled'));
        $this->assertNull($tenant->getSetting('non.existent'));
        $this->assertEquals('default', $tenant->getSetting('non.existent', 'default'));
    }

    public function test_tenant_subscription_checks()
    {
        $tenant = Tenant::create([
            'name' => 'Subscription Tenant',
            'domain' => 'sub.example.com',
            'trial_ends_at' => now()->addDays(7),
            'subscription_ends_at' => now()->addMonths(1),
        ]);

        $this->assertTrue($tenant->isOnTrial());
        $this->assertTrue($tenant->hasActiveSubscription());

        $expiredTenant = Tenant::create([
            'name' => 'Expired Tenant',
            'domain' => 'expired.example.com',
            'trial_ends_at' => now()->subDays(7),
            'subscription_ends_at' => now()->subMonths(1),
        ]);

        $this->assertFalse($expiredTenant->isOnTrial());
        $this->assertFalse($expiredTenant->hasActiveSubscription());
    }
}
