<?php

declare(strict_types=1);

namespace Modules\Tenant\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tenant\Database\Seeders\TenantSeeder;
use Tests\TestCase;

final class TenantSeederConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_seeder_uses_module_configuration_values(): void
    {
        config()->set('tenant.seeding.tenant.code', 'cache-test');
        config()->set('tenant.seeding.tenant.name', 'Cached Tenant');

        (new TenantSeeder())->run();

        $this->assertDatabaseHas('tenants', [
            'code' => 'CACHE-TEST',
            'name' => 'Cached Tenant',
            'slug' => 'cache-test',
        ]);
    }
}