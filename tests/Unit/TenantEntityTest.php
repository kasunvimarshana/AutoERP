<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\ValueObjects\DatabaseConfig;
use Modules\Tenant\Domain\ValueObjects\MailConfig;
use Modules\Tenant\Domain\ValueObjects\CacheConfig;
use Modules\Tenant\Domain\ValueObjects\QueueConfig;
use Modules\Tenant\Domain\ValueObjects\FeatureFlags;
use Modules\Tenant\Domain\ValueObjects\ApiKeys;

class TenantEntityTest extends TestCase
{
    private function createTenant(string $name = 'Test Tenant'): Tenant
    {
        return new Tenant(
            name: $name,
            databaseConfig: new DatabaseConfig([
                'driver'   => 'mysql',
                'host'     => 'localhost',
                'port'     => 3306,
                'database' => 'test_db',
                'username' => 'root',
                'password' => 'secret',
            ]),
            active: true
        );
    }

    public function test_tenant_can_be_created(): void
    {
        $tenant = $this->createTenant('Acme Corp');

        $this->assertSame('Acme Corp', $tenant->getName());
        $this->assertTrue($tenant->isActive());
        $this->assertNull($tenant->getId());
    }

    public function test_tenant_update_method_updates_fields(): void
    {
        $tenant = $this->createTenant('Old Name');

        $tenant->update(
            name: 'New Name',
            domain: 'newdomain.com',
            databaseConfig: new DatabaseConfig(['database' => 'new_db', 'username' => 'user', 'password' => 'pass']),
            active: false,
        );

        $this->assertSame('New Name', $tenant->getName());
        $this->assertSame('newdomain.com', $tenant->getDomain());
        $this->assertFalse($tenant->isActive());
    }

    public function test_tenant_update_config_updates_individual_configs(): void
    {
        $tenant = $this->createTenant();

        $tenant->updateConfig([
            'feature_flags' => ['billing' => true],
            'active'        => false,
        ]);

        $this->assertTrue($tenant->getFeatureFlags()->isEnabled('billing'));
        $this->assertFalse($tenant->isActive());
    }

    public function test_database_config_has_safe_defaults(): void
    {
        $config = new DatabaseConfig([]);

        $this->assertSame('mysql', $config->toArray()['driver']);
        $this->assertSame('127.0.0.1', $config->toArray()['host']);
        $this->assertSame(3306, $config->toArray()['port']);
        $this->assertSame('', $config->toArray()['database']);
        $this->assertSame('', $config->toArray()['username']);
        $this->assertSame('', $config->toArray()['password']);
    }
}
