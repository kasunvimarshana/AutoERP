<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Modules\Auth\Domain\Events\UserLoggedIn;
use Modules\Auth\Domain\Events\UserLoggedOut;
use Modules\Auth\Domain\Events\UserRegistered;
use Modules\Core\Domain\Events\BaseEvent;
use Modules\Core\Domain\Events\UserScopedEvent;
use Modules\Core\Domain\ValueObjects\DatabaseConfig;
use Modules\Core\Domain\ValueObjects\Email;
use Modules\Core\Domain\ValueObjects\Name;
use Modules\Core\Domain\ValueObjects\UserPreferences;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitCreated;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitDeleted;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitMoved;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitUpdated;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\Events\TenantConfigChanged;
use Modules\Tenant\Domain\Events\TenantCreated;
use Modules\Tenant\Domain\Events\TenantDeleted;
use Modules\Tenant\Domain\Events\TenantUpdated;
use Modules\User\Domain\Entities\Role;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\Events\RoleAssigned;
use Modules\User\Domain\Events\UserCreated;
use Modules\User\Domain\Events\UserUpdated;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for domain events across all modules.
 *
 * Verifies that every domain event:
 * - Extends the correct base class (BaseEvent or UserScopedEvent)
 * - Implements ShouldBroadcast
 * - Broadcasts on the correct PrivateChannel(s)
 * - Returns a well-formed payload from broadcastWith()
 * - Derives a sensible event name from broadcastAs()
 */
class DomainEventsTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Tenant Events
    // -----------------------------------------------------------------------

    public function test_tenant_created_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(TenantCreated::class, BaseEvent::class));
    }

    public function test_tenant_created_implements_should_broadcast(): void
    {
        $this->assertTrue(is_subclass_of(TenantCreated::class, ShouldBroadcast::class, true));
    }

    public function test_tenant_created_broadcasts_on_private_tenant_channel(): void
    {
        $tenant = $this->makeTenant(id: 5);
        $event  = new TenantCreated($tenant);

        $names = array_map(fn ($c) => $c->name, $event->broadcastOn());
        $this->assertContains('private-tenant.5', $names);
    }

    public function test_tenant_created_broadcast_with_contains_expected_keys(): void
    {
        $tenant  = $this->makeTenant(id: 3, name: 'Acme', domain: 'acme.com');
        $event   = new TenantCreated($tenant);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('tenantId', $payload);
        $this->assertArrayHasKey('id', $payload);
        $this->assertArrayHasKey('name', $payload);
        $this->assertArrayHasKey('domain', $payload);
        $this->assertArrayHasKey('active', $payload);
        $this->assertSame(3, $payload['id']);
        $this->assertSame('Acme', $payload['name']);
        $this->assertSame('acme.com', $payload['domain']);
    }

    public function test_tenant_updated_broadcasts_on_private_tenant_channel(): void
    {
        $tenant = $this->makeTenant(id: 7);
        $event  = new TenantUpdated($tenant);
        $names  = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.7', $names);
    }

    public function test_tenant_deleted_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(TenantDeleted::class, BaseEvent::class));
    }

    public function test_tenant_deleted_broadcasts_on_private_tenant_channel(): void
    {
        $event = new TenantDeleted(11);
        $names = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.11', $names);
    }

    public function test_tenant_deleted_broadcast_with_contains_id(): void
    {
        $event   = new TenantDeleted(11);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('id', $payload);
        $this->assertSame(11, $payload['id']);
    }

    public function test_tenant_config_changed_broadcasts_on_private_tenant_channel(): void
    {
        $tenant = $this->makeTenant(id: 9);
        $event  = new TenantConfigChanged($tenant);
        $names  = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.9', $names);
    }

    public function test_tenant_event_broadcast_as_returns_short_class_name(): void
    {
        $event = new TenantCreated($this->makeTenant(id: 1));
        $this->assertSame('TenantCreated', $event->broadcastAs());
    }

    // -----------------------------------------------------------------------
    // User Events
    // -----------------------------------------------------------------------

    public function test_user_created_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(UserCreated::class, BaseEvent::class));
    }

    public function test_user_created_implements_should_broadcast(): void
    {
        $this->assertTrue(is_subclass_of(UserCreated::class, ShouldBroadcast::class, true));
    }

    public function test_user_created_broadcasts_on_private_tenant_channel(): void
    {
        $user  = $this->makeUser(id: 1, tenantId: 4);
        $event = new UserCreated($user);
        $names = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.4', $names);
    }

    public function test_user_created_broadcast_with_contains_expected_keys(): void
    {
        $user    = $this->makeUser(id: 42, tenantId: 2, email: 'john@example.com', first: 'John', last: 'Doe');
        $event   = new UserCreated($user);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('tenantId', $payload);
        $this->assertArrayHasKey('id', $payload);
        $this->assertArrayHasKey('email', $payload);
        $this->assertArrayHasKey('firstName', $payload);
        $this->assertArrayHasKey('lastName', $payload);
        $this->assertArrayHasKey('active', $payload);
        $this->assertSame(42, $payload['id']);
        $this->assertSame(2, $payload['tenantId']);
        $this->assertSame('john@example.com', $payload['email']);
    }

    public function test_user_updated_broadcasts_on_private_tenant_channel(): void
    {
        $user  = $this->makeUser(id: 10, tenantId: 6);
        $event = new UserUpdated($user);
        $names = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.6', $names);
    }

    public function test_role_assigned_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(RoleAssigned::class, BaseEvent::class));
    }

    public function test_role_assigned_broadcasts_on_private_tenant_channel(): void
    {
        $user  = $this->makeUser(id: 1, tenantId: 3);
        $role  = $this->makeRole(id: 5, name: 'admin');
        $event = new RoleAssigned($user, $role);
        $names = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.3', $names);
    }

    public function test_role_assigned_broadcast_with_contains_expected_keys(): void
    {
        $user    = $this->makeUser(id: 7, tenantId: 1);
        $role    = $this->makeRole(id: 2, name: 'editor');
        $event   = new RoleAssigned($user, $role);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('userId', $payload);
        $this->assertArrayHasKey('roleId', $payload);
        $this->assertArrayHasKey('roleName', $payload);
        $this->assertSame(7, $payload['userId']);
        $this->assertSame(2, $payload['roleId']);
        $this->assertSame('editor', $payload['roleName']);
    }

    // -----------------------------------------------------------------------
    // OrganizationUnit Events
    // -----------------------------------------------------------------------

    public function test_org_unit_created_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(OrganizationUnitCreated::class, BaseEvent::class));
    }

    public function test_org_unit_created_implements_should_broadcast(): void
    {
        $this->assertTrue(is_subclass_of(OrganizationUnitCreated::class, ShouldBroadcast::class, true));
    }

    public function test_org_unit_created_broadcasts_on_private_tenant_and_org_channels(): void
    {
        $unit  = $this->makeOrgUnit(id: 15, tenantId: 2);
        $event = new OrganizationUnitCreated($unit);
        $names = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.2', $names);
        $this->assertContains('private-org.15', $names);
    }

    public function test_org_unit_created_broadcast_with_contains_expected_keys(): void
    {
        $unit    = $this->makeOrgUnit(id: 8, tenantId: 3, name: 'Engineering');
        $event   = new OrganizationUnitCreated($unit);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('tenantId', $payload);
        $this->assertArrayHasKey('orgUnitId', $payload);
        $this->assertArrayHasKey('id', $payload);
        $this->assertArrayHasKey('name', $payload);
        $this->assertSame(8, $payload['id']);
        $this->assertSame('Engineering', $payload['name']);
    }

    public function test_org_unit_updated_broadcasts_on_private_channels(): void
    {
        $unit  = $this->makeOrgUnit(id: 3, tenantId: 1);
        $event = new OrganizationUnitUpdated($unit);
        $names = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.1', $names);
        $this->assertContains('private-org.3', $names);
    }

    public function test_org_unit_deleted_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(OrganizationUnitDeleted::class, BaseEvent::class));
    }

    public function test_org_unit_deleted_broadcasts_on_private_channels(): void
    {
        $event = new OrganizationUnitDeleted(unitId: 6, tenantId: 4);
        $names = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.4', $names);
        $this->assertContains('private-org.6', $names);
    }

    public function test_org_unit_deleted_broadcast_with_contains_id(): void
    {
        $event   = new OrganizationUnitDeleted(unitId: 6, tenantId: 4);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('id', $payload);
        $this->assertSame(6, $payload['id']);
    }

    public function test_org_unit_moved_broadcasts_on_private_channels(): void
    {
        $unit  = $this->makeOrgUnit(id: 10, tenantId: 5);
        $event = new OrganizationUnitMoved($unit, previousParentId: 2);
        $names = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.5', $names);
        $this->assertContains('private-org.10', $names);
    }

    public function test_org_unit_moved_broadcast_with_contains_expected_keys(): void
    {
        $unit    = $this->makeOrgUnit(id: 10, tenantId: 5);
        $event   = new OrganizationUnitMoved($unit, previousParentId: 3);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('id', $payload);
        $this->assertArrayHasKey('newParentId', $payload);
        $this->assertArrayHasKey('previousParentId', $payload);
        $this->assertSame(3, $payload['previousParentId']);
    }

    // -----------------------------------------------------------------------
    // All events implement ShouldBroadcast
    // -----------------------------------------------------------------------

    public function test_all_domain_events_implement_should_broadcast(): void
    {
        $events = [
            TenantCreated::class,
            TenantUpdated::class,
            TenantDeleted::class,
            TenantConfigChanged::class,
            UserCreated::class,
            UserUpdated::class,
            RoleAssigned::class,
            OrganizationUnitCreated::class,
            OrganizationUnitUpdated::class,
            OrganizationUnitDeleted::class,
            OrganizationUnitMoved::class,
            UserLoggedIn::class,
            UserLoggedOut::class,
            UserRegistered::class,
        ];

        foreach ($events as $eventClass) {
            $this->assertTrue(
                is_subclass_of($eventClass, ShouldBroadcast::class, true),
                "{$eventClass} must implement ShouldBroadcast."
            );
        }
    }

    public function test_all_domain_events_use_private_channels(): void
    {
        $tenant  = $this->makeTenant(id: 1);
        $user    = $this->makeUser(id: 1, tenantId: 1);
        $role    = $this->makeRole(id: 1, name: 'admin');
        $unit    = $this->makeOrgUnit(id: 1, tenantId: 1);

        $instances = [
            new TenantCreated($tenant),
            new TenantUpdated($tenant),
            new TenantDeleted(1),
            new TenantConfigChanged($tenant),
            new UserCreated($user),
            new UserUpdated($user),
            new RoleAssigned($user, $role),
            new OrganizationUnitCreated($unit),
            new OrganizationUnitUpdated($unit),
            new OrganizationUnitDeleted(unitId: 1, tenantId: 1),
            new OrganizationUnitMoved($unit, null),
            new UserLoggedIn(1, 'a@b.com'),
            new UserLoggedOut(1, 'a@b.com'),
            new UserRegistered(1, 'a@b.com', 'A', 'B'),
        ];

        foreach ($instances as $event) {
            $channels = $event->broadcastOn();
            $this->assertNotEmpty($channels, get_class($event).' must broadcast on at least one channel.');
            foreach ($channels as $channel) {
                $this->assertInstanceOf(PrivateChannel::class, $channel,
                    get_class($event).' channels must be PrivateChannel instances.');
            }
        }
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function makeTenant(int $id, string $name = 'Test Tenant', ?string $domain = null): Tenant
    {
        $db = new DatabaseConfig('sqlite', ':memory:', 0, 'testdb', 'root', '');

        return new Tenant(name: $name, databaseConfig: $db, domain: $domain, id: $id);
    }

    private function makeUser(
        int $id,
        int $tenantId,
        string $email = 'user@example.com',
        string $first = 'First',
        string $last = 'Last',
    ): User {
        return new User(
            tenantId: $tenantId,
            email: new Email($email),
            firstName: $first,
            lastName: $last,
            preferences: new UserPreferences,
            id: $id,
        );
    }

    private function makeRole(int $id, string $name): Role
    {
        return new Role(tenantId: 1, name: $name, id: $id);
    }

    private function makeOrgUnit(int $id, int $tenantId, string $name = 'Unit'): OrganizationUnit
    {
        return new OrganizationUnit(tenantId: $tenantId, name: new Name($name), id: $id);
    }
}
