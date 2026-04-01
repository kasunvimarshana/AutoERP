<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Modules\Account\Domain\Entities\Account;
use Modules\Account\Domain\Events\AccountCreated;
use Modules\Account\Domain\Events\AccountDeleted;
use Modules\Account\Domain\Events\AccountUpdated;
use Modules\Auth\Domain\Events\UserLoggedIn;
use Modules\Auth\Domain\Events\UserLoggedOut;
use Modules\Auth\Domain\Events\UserRegistered;
use Modules\Brand\Domain\Entities\Brand;
use Modules\Brand\Domain\Events\BrandCreated;
use Modules\Brand\Domain\Events\BrandDeleted;
use Modules\Brand\Domain\Events\BrandUpdated;
use Modules\Category\Domain\Entities\Category;
use Modules\Category\Domain\Events\CategoryCreated;
use Modules\Category\Domain\Events\CategoryDeleted;
use Modules\Category\Domain\Events\CategoryUpdated;
use Modules\Core\Domain\Events\BaseEvent;
use Modules\Core\Domain\Events\UserScopedEvent;
use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\DatabaseConfig;
use Modules\Core\Domain\ValueObjects\Email;
use Modules\Core\Domain\ValueObjects\Money;
use Modules\Core\Domain\ValueObjects\Name;
use Modules\Core\Domain\ValueObjects\Sku;
use Modules\Core\Domain\ValueObjects\UserPreferences;
use Modules\Customer\Domain\Entities\Customer;
use Modules\Customer\Domain\Events\CustomerCreated;
use Modules\Customer\Domain\Events\CustomerDeleted;
use Modules\Customer\Domain\Events\CustomerUpdated;
use Modules\Location\Domain\Entities\Location;
use Modules\Location\Domain\Events\LocationCreated;
use Modules\Location\Domain\Events\LocationDeleted;
use Modules\Location\Domain\Events\LocationMoved;
use Modules\Location\Domain\Events\LocationUpdated;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitCreated;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitDeleted;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitMoved;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitUpdated;
use Modules\Product\Domain\Entities\ComboItem;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Domain\Entities\ProductVariation;
use Modules\Product\Domain\Events\ComboItemCreated;
use Modules\Product\Domain\Events\ComboItemDeleted;
use Modules\Product\Domain\Events\ComboItemUpdated;
use Modules\Product\Domain\Events\ProductCreated;
use Modules\Product\Domain\Events\ProductDeleted;
use Modules\Product\Domain\Events\ProductUpdated;
use Modules\Product\Domain\Events\ProductVariationCreated;
use Modules\Product\Domain\Events\ProductVariationDeleted;
use Modules\Product\Domain\Events\ProductVariationUpdated;
use Modules\Supplier\Domain\Entities\Supplier;
use Modules\Supplier\Domain\Events\SupplierCreated;
use Modules\Supplier\Domain\Events\SupplierDeleted;
use Modules\Supplier\Domain\Events\SupplierUpdated;
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
use Modules\Warehouse\Domain\Entities\Warehouse;
use Modules\Warehouse\Domain\Entities\WarehouseZone;
use Modules\Warehouse\Domain\Events\WarehouseCreated;
use Modules\Warehouse\Domain\Events\WarehouseDeleted;
use Modules\Warehouse\Domain\Events\WarehouseUpdated;
use Modules\Warehouse\Domain\Events\WarehouseZoneCreated;
use Modules\Warehouse\Domain\Events\WarehouseZoneDeleted;
use Modules\Warehouse\Domain\Events\WarehouseZoneUpdated;
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
    // Account Events
    // -----------------------------------------------------------------------

    public function test_account_created_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(AccountCreated::class, BaseEvent::class));
    }

    public function test_account_created_implements_should_broadcast(): void
    {
        $this->assertTrue(is_subclass_of(AccountCreated::class, ShouldBroadcast::class, true));
    }

    public function test_account_created_broadcasts_on_private_tenant_channel(): void
    {
        $account = $this->makeAccount(id: 1, tenantId: 5);
        $event   = new AccountCreated($account);
        $names   = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.5', $names);
    }

    public function test_account_created_broadcast_with_contains_expected_keys(): void
    {
        $account = $this->makeAccount(id: 10, tenantId: 2, code: 'ACC001', name: 'Cash', type: 'asset');
        $event   = new AccountCreated($account);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('tenantId', $payload);
        $this->assertArrayHasKey('id', $payload);
        $this->assertArrayHasKey('code', $payload);
        $this->assertArrayHasKey('name', $payload);
        $this->assertArrayHasKey('type', $payload);
        $this->assertSame(10, $payload['id']);
        $this->assertSame('ACC001', $payload['code']);
        $this->assertSame('Cash', $payload['name']);
    }

    public function test_account_updated_broadcasts_on_private_tenant_channel(): void
    {
        $account = $this->makeAccount(id: 1, tenantId: 7);
        $event   = new AccountUpdated($account);
        $names   = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.7', $names);
    }

    public function test_account_deleted_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(AccountDeleted::class, BaseEvent::class));
    }

    public function test_account_deleted_broadcasts_on_private_tenant_channel(): void
    {
        $event = new AccountDeleted(accountId: 3, tenantId: 8);
        $names = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.8', $names);
    }

    public function test_account_deleted_broadcast_with_contains_id(): void
    {
        $event   = new AccountDeleted(accountId: 3, tenantId: 8);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('id', $payload);
        $this->assertSame(3, $payload['id']);
    }

    // -----------------------------------------------------------------------
    // Brand Events
    // -----------------------------------------------------------------------

    public function test_brand_created_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(BrandCreated::class, BaseEvent::class));
    }

    public function test_brand_created_implements_should_broadcast(): void
    {
        $this->assertTrue(is_subclass_of(BrandCreated::class, ShouldBroadcast::class, true));
    }

    public function test_brand_created_broadcasts_on_private_tenant_channel(): void
    {
        $brand = $this->makeBrand(id: 1, tenantId: 4);
        $event = new BrandCreated($brand);
        $names = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.4', $names);
    }

    public function test_brand_created_broadcast_with_contains_expected_keys(): void
    {
        $brand   = $this->makeBrand(id: 5, tenantId: 2, name: 'Acme', slug: 'acme');
        $event   = new BrandCreated($brand);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('tenantId', $payload);
        $this->assertArrayHasKey('id', $payload);
        $this->assertArrayHasKey('name', $payload);
        $this->assertArrayHasKey('slug', $payload);
        $this->assertSame(5, $payload['id']);
        $this->assertSame('Acme', $payload['name']);
        $this->assertSame('acme', $payload['slug']);
    }

    public function test_brand_updated_broadcasts_on_private_tenant_channel(): void
    {
        $brand = $this->makeBrand(id: 1, tenantId: 6);
        $event = new BrandUpdated($brand);
        $names = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.6', $names);
    }

    public function test_brand_deleted_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(BrandDeleted::class, BaseEvent::class));
    }

    public function test_brand_deleted_broadcasts_on_private_tenant_channel(): void
    {
        $event = new BrandDeleted(brandId: 7, tenantId: 3);
        $names = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.3', $names);
    }

    public function test_brand_deleted_broadcast_with_contains_id(): void
    {
        $event   = new BrandDeleted(brandId: 7, tenantId: 3);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('id', $payload);
        $this->assertSame(7, $payload['id']);
    }

    // -----------------------------------------------------------------------
    // Category Events
    // -----------------------------------------------------------------------

    public function test_category_created_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(CategoryCreated::class, BaseEvent::class));
    }

    public function test_category_created_implements_should_broadcast(): void
    {
        $this->assertTrue(is_subclass_of(CategoryCreated::class, ShouldBroadcast::class, true));
    }

    public function test_category_created_broadcasts_on_private_tenant_channel(): void
    {
        $category = $this->makeCategory(id: 1, tenantId: 3);
        $event    = new CategoryCreated($category);
        $names    = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.3', $names);
    }

    public function test_category_created_broadcast_with_contains_expected_keys(): void
    {
        $category = $this->makeCategory(id: 9, tenantId: 2, name: 'Electronics', slug: 'electronics');
        $event    = new CategoryCreated($category);
        $payload  = $event->broadcastWith();

        $this->assertArrayHasKey('tenantId', $payload);
        $this->assertArrayHasKey('id', $payload);
        $this->assertArrayHasKey('name', $payload);
        $this->assertArrayHasKey('slug', $payload);
        $this->assertSame(9, $payload['id']);
        $this->assertSame('Electronics', $payload['name']);
    }

    public function test_category_updated_broadcasts_on_private_tenant_channel(): void
    {
        $category = $this->makeCategory(id: 1, tenantId: 5);
        $event    = new CategoryUpdated($category);
        $names    = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.5', $names);
    }

    public function test_category_deleted_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(CategoryDeleted::class, BaseEvent::class));
    }

    public function test_category_deleted_broadcasts_on_private_tenant_channel(): void
    {
        $event = new CategoryDeleted(categoryId: 4, tenantId: 2);
        $names = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.2', $names);
    }

    public function test_category_deleted_broadcast_with_contains_id(): void
    {
        $event   = new CategoryDeleted(categoryId: 4, tenantId: 2);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('id', $payload);
        $this->assertSame(4, $payload['id']);
    }

    // -----------------------------------------------------------------------
    // Customer Events
    // -----------------------------------------------------------------------

    public function test_customer_created_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(CustomerCreated::class, BaseEvent::class));
    }

    public function test_customer_created_implements_should_broadcast(): void
    {
        $this->assertTrue(is_subclass_of(CustomerCreated::class, ShouldBroadcast::class, true));
    }

    public function test_customer_created_broadcasts_on_private_tenant_channel(): void
    {
        $customer = $this->makeCustomer(id: 1, tenantId: 4);
        $event    = new CustomerCreated($customer);
        $names    = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.4', $names);
    }

    public function test_customer_created_broadcast_with_contains_expected_keys(): void
    {
        $customer = $this->makeCustomer(id: 11, tenantId: 3, name: 'John Doe', code: 'CUST001');
        $event    = new CustomerCreated($customer);
        $payload  = $event->broadcastWith();

        $this->assertArrayHasKey('tenantId', $payload);
        $this->assertArrayHasKey('id', $payload);
        $this->assertArrayHasKey('name', $payload);
        $this->assertArrayHasKey('code', $payload);
        $this->assertSame(11, $payload['id']);
        $this->assertSame('John Doe', $payload['name']);
        $this->assertSame('CUST001', $payload['code']);
    }

    public function test_customer_updated_broadcasts_on_private_tenant_channel(): void
    {
        $customer = $this->makeCustomer(id: 1, tenantId: 6);
        $event    = new CustomerUpdated($customer);
        $names    = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.6', $names);
    }

    public function test_customer_deleted_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(CustomerDeleted::class, BaseEvent::class));
    }

    public function test_customer_deleted_broadcasts_on_private_tenant_channel(): void
    {
        $event = new CustomerDeleted(customerId: 5, tenantId: 2);
        $names = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.2', $names);
    }

    public function test_customer_deleted_broadcast_with_contains_id(): void
    {
        $event   = new CustomerDeleted(customerId: 5, tenantId: 2);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('id', $payload);
        $this->assertSame(5, $payload['id']);
    }

    // -----------------------------------------------------------------------
    // Location Events
    // -----------------------------------------------------------------------

    public function test_location_created_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(LocationCreated::class, BaseEvent::class));
    }

    public function test_location_created_implements_should_broadcast(): void
    {
        $this->assertTrue(is_subclass_of(LocationCreated::class, ShouldBroadcast::class, true));
    }

    public function test_location_created_broadcasts_on_private_tenant_and_org_channels(): void
    {
        $location = $this->makeLocation(id: 20, tenantId: 3);
        $event    = new LocationCreated($location);
        $names    = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.3', $names);
        $this->assertContains('private-org.20', $names);
    }

    public function test_location_created_broadcast_with_contains_expected_keys(): void
    {
        $location = $this->makeLocation(id: 7, tenantId: 2, name: 'Colombo', type: 'city');
        $event    = new LocationCreated($location);
        $payload  = $event->broadcastWith();

        $this->assertArrayHasKey('tenantId', $payload);
        $this->assertArrayHasKey('id', $payload);
        $this->assertArrayHasKey('name', $payload);
        $this->assertArrayHasKey('type', $payload);
        $this->assertSame(7, $payload['id']);
        $this->assertSame('Colombo', $payload['name']);
        $this->assertSame('city', $payload['type']);
    }

    public function test_location_updated_broadcasts_on_private_channels(): void
    {
        $location = $this->makeLocation(id: 4, tenantId: 1);
        $event    = new LocationUpdated($location);
        $names    = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.1', $names);
        $this->assertContains('private-org.4', $names);
    }

    public function test_location_deleted_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(LocationDeleted::class, BaseEvent::class));
    }

    public function test_location_deleted_broadcasts_on_private_channels(): void
    {
        $event = new LocationDeleted(locationId: 8, tenantId: 5);
        $names = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.5', $names);
        $this->assertContains('private-org.8', $names);
    }

    public function test_location_deleted_broadcast_with_contains_id(): void
    {
        $event   = new LocationDeleted(locationId: 8, tenantId: 5);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('id', $payload);
        $this->assertSame(8, $payload['id']);
    }

    public function test_location_moved_broadcasts_on_private_channels(): void
    {
        $location = $this->makeLocation(id: 12, tenantId: 2);
        $event    = new LocationMoved($location, oldParentId: 3);
        $names    = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.2', $names);
        $this->assertContains('private-org.12', $names);
    }

    public function test_location_moved_broadcast_with_contains_expected_keys(): void
    {
        $location = $this->makeLocation(id: 12, tenantId: 2);
        $event    = new LocationMoved($location, oldParentId: 5);
        $payload  = $event->broadcastWith();

        $this->assertArrayHasKey('id', $payload);
        $this->assertArrayHasKey('parentId', $payload);
        $this->assertArrayHasKey('oldParentId', $payload);
        $this->assertSame(12, $payload['id']);
        $this->assertSame(5, $payload['oldParentId']);
    }

    // -----------------------------------------------------------------------
    // Product Events
    // -----------------------------------------------------------------------

    public function test_product_created_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(ProductCreated::class, BaseEvent::class));
    }

    public function test_product_created_implements_should_broadcast(): void
    {
        $this->assertTrue(is_subclass_of(ProductCreated::class, ShouldBroadcast::class, true));
    }

    public function test_product_created_broadcasts_on_private_tenant_channel(): void
    {
        $product = $this->makeProduct(id: 1, tenantId: 4);
        $event   = new ProductCreated($product);
        $names   = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.4', $names);
    }

    public function test_product_created_broadcast_with_contains_expected_keys(): void
    {
        $product = $this->makeProduct(id: 15, tenantId: 2, sku: 'SKU-100', name: 'Widget');
        $event   = new ProductCreated($product);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('tenantId', $payload);
        $this->assertArrayHasKey('id', $payload);
        $this->assertArrayHasKey('sku', $payload);
        $this->assertArrayHasKey('name', $payload);
        $this->assertSame(15, $payload['id']);
        $this->assertSame('SKU-100', $payload['sku']);
        $this->assertSame('Widget', $payload['name']);
    }

    public function test_product_updated_broadcasts_on_private_tenant_channel(): void
    {
        $product = $this->makeProduct(id: 1, tenantId: 5);
        $event   = new ProductUpdated($product);
        $names   = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.5', $names);
    }

    public function test_product_deleted_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(ProductDeleted::class, BaseEvent::class));
    }

    public function test_product_deleted_broadcasts_on_private_tenant_channel(): void
    {
        $event = new ProductDeleted(productId: 6, tenantId: 3);
        $names = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.3', $names);
    }

    public function test_product_deleted_broadcast_with_contains_id(): void
    {
        $event   = new ProductDeleted(productId: 6, tenantId: 3);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('id', $payload);
        $this->assertSame(6, $payload['id']);
    }

    public function test_product_variation_created_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(ProductVariationCreated::class, BaseEvent::class));
    }

    public function test_product_variation_created_broadcasts_on_private_tenant_channel(): void
    {
        $variation = $this->makeProductVariation(id: 1, productId: 2, tenantId: 3);
        $event     = new ProductVariationCreated($variation);
        $names     = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.3', $names);
    }

    public function test_product_variation_created_broadcast_with_contains_expected_keys(): void
    {
        $variation = $this->makeProductVariation(id: 5, productId: 10, tenantId: 2, sku: 'SKU-M');
        $event     = new ProductVariationCreated($variation);
        $payload   = $event->broadcastWith();

        $this->assertArrayHasKey('id', $payload);
        $this->assertArrayHasKey('product_id', $payload);
        $this->assertArrayHasKey('sku', $payload);
        $this->assertSame(5, $payload['id']);
        $this->assertSame(10, $payload['product_id']);
    }

    public function test_product_variation_updated_broadcasts_on_private_tenant_channel(): void
    {
        $variation = $this->makeProductVariation(id: 1, productId: 1, tenantId: 4);
        $event     = new ProductVariationUpdated($variation);
        $names     = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.4', $names);
    }

    public function test_product_variation_deleted_broadcasts_on_private_tenant_channel(): void
    {
        $event = new ProductVariationDeleted(variationId: 3, tenantId: 2);
        $names = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.2', $names);
    }

    public function test_product_variation_deleted_broadcast_with_contains_id(): void
    {
        $event   = new ProductVariationDeleted(variationId: 3, tenantId: 2);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('id', $payload);
        $this->assertSame(3, $payload['id']);
    }

    public function test_combo_item_created_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(ComboItemCreated::class, BaseEvent::class));
    }

    public function test_combo_item_created_broadcasts_on_private_tenant_channel(): void
    {
        $comboItem = $this->makeComboItem(id: 1, productId: 1, tenantId: 5);
        $event     = new ComboItemCreated($comboItem);
        $names     = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.5', $names);
    }

    public function test_combo_item_created_broadcast_with_contains_expected_keys(): void
    {
        $comboItem = $this->makeComboItem(id: 2, productId: 7, tenantId: 1, componentProductId: 9);
        $event     = new ComboItemCreated($comboItem);
        $payload   = $event->broadcastWith();

        $this->assertArrayHasKey('id', $payload);
        $this->assertArrayHasKey('product_id', $payload);
        $this->assertArrayHasKey('component_product_id', $payload);
        $this->assertArrayHasKey('quantity', $payload);
        $this->assertSame(2, $payload['id']);
        $this->assertSame(7, $payload['product_id']);
        $this->assertSame(9, $payload['component_product_id']);
    }

    public function test_combo_item_updated_broadcasts_on_private_tenant_channel(): void
    {
        $comboItem = $this->makeComboItem(id: 1, productId: 1, tenantId: 3);
        $event     = new ComboItemUpdated($comboItem);
        $names     = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.3', $names);
    }

    public function test_combo_item_deleted_broadcasts_on_private_tenant_channel(): void
    {
        $event = new ComboItemDeleted(comboItemId: 4, tenantId: 2);
        $names = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.2', $names);
    }

    public function test_combo_item_deleted_broadcast_with_contains_id(): void
    {
        $event   = new ComboItemDeleted(comboItemId: 4, tenantId: 2);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('id', $payload);
        $this->assertSame(4, $payload['id']);
    }

    // -----------------------------------------------------------------------
    // Supplier Events
    // -----------------------------------------------------------------------

    public function test_supplier_created_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(SupplierCreated::class, BaseEvent::class));
    }

    public function test_supplier_created_implements_should_broadcast(): void
    {
        $this->assertTrue(is_subclass_of(SupplierCreated::class, ShouldBroadcast::class, true));
    }

    public function test_supplier_created_broadcasts_on_private_tenant_channel(): void
    {
        $supplier = $this->makeSupplier(id: 1, tenantId: 4);
        $event    = new SupplierCreated($supplier);
        $names    = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.4', $names);
    }

    public function test_supplier_created_broadcast_with_contains_expected_keys(): void
    {
        $supplier = $this->makeSupplier(id: 8, tenantId: 2, name: 'Global Supply Co', code: 'SUPP001');
        $event    = new SupplierCreated($supplier);
        $payload  = $event->broadcastWith();

        $this->assertArrayHasKey('tenantId', $payload);
        $this->assertArrayHasKey('id', $payload);
        $this->assertArrayHasKey('name', $payload);
        $this->assertArrayHasKey('code', $payload);
        $this->assertSame(8, $payload['id']);
        $this->assertSame('Global Supply Co', $payload['name']);
        $this->assertSame('SUPP001', $payload['code']);
    }

    public function test_supplier_updated_broadcasts_on_private_tenant_channel(): void
    {
        $supplier = $this->makeSupplier(id: 1, tenantId: 6);
        $event    = new SupplierUpdated($supplier);
        $names    = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.6', $names);
    }

    public function test_supplier_deleted_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(SupplierDeleted::class, BaseEvent::class));
    }

    public function test_supplier_deleted_broadcasts_on_private_tenant_channel(): void
    {
        $event = new SupplierDeleted(supplierId: 9, tenantId: 3);
        $names = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.3', $names);
    }

    public function test_supplier_deleted_broadcast_with_contains_id(): void
    {
        $event   = new SupplierDeleted(supplierId: 9, tenantId: 3);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('id', $payload);
        $this->assertSame(9, $payload['id']);
    }

    // -----------------------------------------------------------------------
    // Warehouse Events
    // -----------------------------------------------------------------------

    public function test_warehouse_created_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(WarehouseCreated::class, BaseEvent::class));
    }

    public function test_warehouse_created_implements_should_broadcast(): void
    {
        $this->assertTrue(is_subclass_of(WarehouseCreated::class, ShouldBroadcast::class, true));
    }

    public function test_warehouse_created_broadcasts_on_private_tenant_and_org_channels(): void
    {
        $warehouse = $this->makeWarehouse(id: 30, tenantId: 4);
        $event     = new WarehouseCreated($warehouse);
        $names     = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.4', $names);
        $this->assertContains('private-org.30', $names);
    }

    public function test_warehouse_created_broadcast_with_contains_expected_keys(): void
    {
        $warehouse = $this->makeWarehouse(id: 6, tenantId: 2, name: 'Main Warehouse', type: 'distribution');
        $event     = new WarehouseCreated($warehouse);
        $payload   = $event->broadcastWith();

        $this->assertArrayHasKey('tenantId', $payload);
        $this->assertArrayHasKey('id', $payload);
        $this->assertArrayHasKey('name', $payload);
        $this->assertArrayHasKey('type', $payload);
        $this->assertSame(6, $payload['id']);
        $this->assertSame('Main Warehouse', $payload['name']);
        $this->assertSame('distribution', $payload['type']);
    }

    public function test_warehouse_updated_broadcasts_on_private_channels(): void
    {
        $warehouse = $this->makeWarehouse(id: 5, tenantId: 3);
        $event     = new WarehouseUpdated($warehouse);
        $names     = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.3', $names);
        $this->assertContains('private-org.5', $names);
    }

    public function test_warehouse_deleted_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(WarehouseDeleted::class, BaseEvent::class));
    }

    public function test_warehouse_deleted_broadcasts_on_private_channels(): void
    {
        $event = new WarehouseDeleted(warehouseId: 11, tenantId: 2);
        $names = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.2', $names);
        $this->assertContains('private-org.11', $names);
    }

    public function test_warehouse_deleted_broadcast_with_contains_id(): void
    {
        $event   = new WarehouseDeleted(warehouseId: 11, tenantId: 2);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('id', $payload);
        $this->assertSame(11, $payload['id']);
    }

    public function test_warehouse_zone_created_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(WarehouseZoneCreated::class, BaseEvent::class));
    }

    public function test_warehouse_zone_created_broadcasts_on_private_channels(): void
    {
        $zone  = $this->makeWarehouseZone(id: 25, warehouseId: 3, tenantId: 2);
        $event = new WarehouseZoneCreated($zone);
        $names = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.2', $names);
        $this->assertContains('private-org.25', $names);
    }

    public function test_warehouse_zone_created_broadcast_with_contains_expected_keys(): void
    {
        $zone    = $this->makeWarehouseZone(id: 4, warehouseId: 2, tenantId: 1, name: 'Aisle A', type: 'picking');
        $event   = new WarehouseZoneCreated($zone);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('id', $payload);
        $this->assertArrayHasKey('warehouseId', $payload);
        $this->assertArrayHasKey('name', $payload);
        $this->assertArrayHasKey('type', $payload);
        $this->assertSame(4, $payload['id']);
        $this->assertSame(2, $payload['warehouseId']);
        $this->assertSame('Aisle A', $payload['name']);
    }

    public function test_warehouse_zone_updated_broadcasts_on_private_channels(): void
    {
        $zone  = $this->makeWarehouseZone(id: 7, warehouseId: 1, tenantId: 3);
        $event = new WarehouseZoneUpdated($zone);
        $names = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.3', $names);
        $this->assertContains('private-org.7', $names);
    }

    public function test_warehouse_zone_deleted_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(WarehouseZoneDeleted::class, BaseEvent::class));
    }

    public function test_warehouse_zone_deleted_broadcasts_on_private_channels(): void
    {
        $event = new WarehouseZoneDeleted(zoneId: 14, tenantId: 5);
        $names = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.5', $names);
        $this->assertContains('private-org.14', $names);
    }

    public function test_warehouse_zone_deleted_broadcast_with_contains_id(): void
    {
        $event   = new WarehouseZoneDeleted(zoneId: 14, tenantId: 5);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('id', $payload);
        $this->assertSame(14, $payload['id']);
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
            AccountCreated::class,
            AccountUpdated::class,
            AccountDeleted::class,
            BrandCreated::class,
            BrandUpdated::class,
            BrandDeleted::class,
            CategoryCreated::class,
            CategoryUpdated::class,
            CategoryDeleted::class,
            CustomerCreated::class,
            CustomerUpdated::class,
            CustomerDeleted::class,
            LocationCreated::class,
            LocationUpdated::class,
            LocationDeleted::class,
            LocationMoved::class,
            ProductCreated::class,
            ProductUpdated::class,
            ProductDeleted::class,
            ProductVariationCreated::class,
            ProductVariationUpdated::class,
            ProductVariationDeleted::class,
            ComboItemCreated::class,
            ComboItemUpdated::class,
            ComboItemDeleted::class,
            SupplierCreated::class,
            SupplierUpdated::class,
            SupplierDeleted::class,
            WarehouseCreated::class,
            WarehouseUpdated::class,
            WarehouseDeleted::class,
            WarehouseZoneCreated::class,
            WarehouseZoneUpdated::class,
            WarehouseZoneDeleted::class,
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
        $tenant      = $this->makeTenant(id: 1);
        $user        = $this->makeUser(id: 1, tenantId: 1);
        $role        = $this->makeRole(id: 1, name: 'admin');
        $unit        = $this->makeOrgUnit(id: 1, tenantId: 1);
        $account     = $this->makeAccount(id: 1, tenantId: 1);
        $brand       = $this->makeBrand(id: 1, tenantId: 1);
        $category    = $this->makeCategory(id: 1, tenantId: 1);
        $customer    = $this->makeCustomer(id: 1, tenantId: 1);
        $location    = $this->makeLocation(id: 1, tenantId: 1);
        $product     = $this->makeProduct(id: 1, tenantId: 1);
        $variation   = $this->makeProductVariation(id: 1, productId: 1, tenantId: 1);
        $comboItem   = $this->makeComboItem(id: 1, productId: 1, tenantId: 1);
        $supplier    = $this->makeSupplier(id: 1, tenantId: 1);
        $warehouse   = $this->makeWarehouse(id: 1, tenantId: 1);
        $zone        = $this->makeWarehouseZone(id: 1, warehouseId: 1, tenantId: 1);

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
            new AccountCreated($account),
            new AccountUpdated($account),
            new AccountDeleted(accountId: 1, tenantId: 1),
            new BrandCreated($brand),
            new BrandUpdated($brand),
            new BrandDeleted(brandId: 1, tenantId: 1),
            new CategoryCreated($category),
            new CategoryUpdated($category),
            new CategoryDeleted(categoryId: 1, tenantId: 1),
            new CustomerCreated($customer),
            new CustomerUpdated($customer),
            new CustomerDeleted(customerId: 1, tenantId: 1),
            new LocationCreated($location),
            new LocationUpdated($location),
            new LocationDeleted(locationId: 1, tenantId: 1),
            new LocationMoved($location, null),
            new ProductCreated($product),
            new ProductUpdated($product),
            new ProductDeleted(productId: 1, tenantId: 1),
            new ProductVariationCreated($variation),
            new ProductVariationUpdated($variation),
            new ProductVariationDeleted(variationId: 1, tenantId: 1),
            new ComboItemCreated($comboItem),
            new ComboItemUpdated($comboItem),
            new ComboItemDeleted(comboItemId: 1, tenantId: 1),
            new SupplierCreated($supplier),
            new SupplierUpdated($supplier),
            new SupplierDeleted(supplierId: 1, tenantId: 1),
            new WarehouseCreated($warehouse),
            new WarehouseUpdated($warehouse),
            new WarehouseDeleted(warehouseId: 1, tenantId: 1),
            new WarehouseZoneCreated($zone),
            new WarehouseZoneUpdated($zone),
            new WarehouseZoneDeleted(zoneId: 1, tenantId: 1),
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

    private function makeAccount(
        int $id,
        int $tenantId,
        string $code = 'ACC001',
        string $name = 'Test Account',
        string $type = 'asset',
    ): Account {
        return new Account(tenantId: $tenantId, code: $code, name: $name, type: $type, id: $id);
    }

    private function makeBrand(
        int $id,
        int $tenantId,
        string $name = 'Test Brand',
        string $slug = 'test-brand',
    ): Brand {
        return new Brand(tenantId: $tenantId, name: $name, slug: $slug, id: $id);
    }

    private function makeCategory(
        int $id,
        int $tenantId,
        string $name = 'Test Category',
        string $slug = 'test-category',
    ): Category {
        return new Category(tenantId: $tenantId, name: $name, slug: $slug, id: $id);
    }

    private function makeCustomer(
        int $id,
        int $tenantId,
        string $name = 'Test Customer',
        string $code = 'CUST001',
    ): Customer {
        return new Customer(tenantId: $tenantId, name: $name, code: $code, id: $id);
    }

    private function makeLocation(
        int $id,
        int $tenantId,
        string $name = 'Test Location',
        string $type = 'city',
    ): Location {
        return new Location(tenantId: $tenantId, name: new Name($name), type: $type, id: $id);
    }

    private function makeProduct(
        int $id,
        int $tenantId,
        string $sku = 'SKU-001',
        string $name = 'Test Product',
    ): Product {
        return new Product(
            tenantId: $tenantId,
            sku: new Sku($sku),
            name: $name,
            price: new Money(10.0),
            id: $id,
        );
    }

    private function makeProductVariation(
        int $id,
        int $productId,
        int $tenantId,
        string $sku = 'SKU-VAR-001',
        string $name = 'Test Variation',
    ): ProductVariation {
        return new ProductVariation(
            productId: $productId,
            tenantId: $tenantId,
            sku: new Sku($sku),
            name: $name,
            price: new Money(12.0),
            id: $id,
        );
    }

    private function makeComboItem(
        int $id,
        int $productId,
        int $tenantId,
        int $componentProductId = 2,
        float $quantity = 1.0,
    ): ComboItem {
        return new ComboItem(
            productId: $productId,
            tenantId: $tenantId,
            componentProductId: $componentProductId,
            quantity: $quantity,
            id: $id,
        );
    }

    private function makeSupplier(
        int $id,
        int $tenantId,
        string $name = 'Test Supplier',
        string $code = 'SUPP001',
    ): Supplier {
        return new Supplier(tenantId: $tenantId, name: $name, code: $code, id: $id);
    }

    private function makeWarehouse(
        int $id,
        int $tenantId,
        string $name = 'Test Warehouse',
        string $type = 'main',
    ): Warehouse {
        return new Warehouse(tenantId: $tenantId, name: new Name($name), type: $type, id: $id);
    }

    private function makeWarehouseZone(
        int $id,
        int $warehouseId,
        int $tenantId,
        string $name = 'Test Zone',
        string $type = 'picking',
    ): WarehouseZone {
        return new WarehouseZone(
            warehouseId: $warehouseId,
            tenantId: $tenantId,
            name: new Name($name),
            type: $type,
            id: $id,
        );
    }
}
