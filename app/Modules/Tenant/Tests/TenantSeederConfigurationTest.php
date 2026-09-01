<?php

declare(strict_types=1);

namespace Modules\Tenant\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tenant\Constants\TenantStatus;
use Modules\Tenant\Database\Seeders\TenantSeeder;
use Modules\Tenant\Models\TenantModel;
use Tests\TestCase;

final class TenantSeederConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_seeder_uses_module_configuration_values(): void
    {
        config()->set('tenant.seeding.tenant.code', 'cache-test');
        config()->set('tenant.seeding.tenant.name', 'Cached Tenant');

        (new TenantSeeder)->run();

        $this->assertDatabaseHas('tenants', [
            'code' => 'CACHE-TEST',
            'name' => 'Cached Tenant',
            'slug' => 'cache-test',
        ]);
    }

    public function test_tenant_seeder_preserves_existing_tenant_lifecycle_state(): void
    {
        config()->set('tenant.seeding.tenant.code', 'active-local');
        config()->set('tenant.seeding.tenant.name', 'Active Local Tenant');

        (new TenantSeeder)->run();

        $activatedAt = now()->subDay()->startOfSecond();
        $tenant = TenantModel::query()->where('code', 'ACTIVE-LOCAL')->firstOrFail();
        $tenant->forceFill([
            'status' => TenantStatus::ACTIVE,
            'status_changed_at' => $activatedAt,
            'status_reason' => 'Local tenant activated.',
            'activated_at' => $activatedAt,
            'row_version' => 7,
        ])->save();

        (new TenantSeeder)->run();

        $tenant->refresh();
        $this->assertSame(TenantStatus::ACTIVE, $tenant->status);
        $this->assertSame('Local tenant activated.', $tenant->status_reason);
        $this->assertSame(7, (int) $tenant->row_version);
        $this->assertTrue($activatedAt->equalTo($tenant->activated_at));
    }
}
