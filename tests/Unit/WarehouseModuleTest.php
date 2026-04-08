<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Support\Collection;
use Modules\Warehouse\Application\Contracts\CreateWarehouseServiceInterface;
use Modules\Warehouse\Application\Contracts\CreateWarehouseZoneServiceInterface;
use Modules\Warehouse\Application\Contracts\DeleteWarehouseServiceInterface;
use Modules\Warehouse\Application\Contracts\DeleteWarehouseZoneServiceInterface;
use Modules\Warehouse\Application\Contracts\FindWarehouseServiceInterface;
use Modules\Warehouse\Application\Contracts\FindWarehouseZoneServiceInterface;
use Modules\Warehouse\Application\Contracts\UpdateWarehouseServiceInterface;
use Modules\Warehouse\Application\Contracts\UpdateWarehouseZoneServiceInterface;
use Modules\Warehouse\Application\DTOs\UpdateWarehouseData;
use Modules\Warehouse\Application\DTOs\UpdateWarehouseZoneData;
use Modules\Warehouse\Application\DTOs\WarehouseData;
use Modules\Warehouse\Application\DTOs\WarehouseZoneData;
use Modules\Warehouse\Application\Services\CreateWarehouseService;
use Modules\Warehouse\Application\Services\CreateWarehouseZoneService;
use Modules\Warehouse\Application\Services\DeleteWarehouseService;
use Modules\Warehouse\Application\Services\DeleteWarehouseZoneService;
use Modules\Warehouse\Application\Services\FindWarehouseService;
use Modules\Warehouse\Application\Services\FindWarehouseZoneService;
use Modules\Warehouse\Application\Services\UpdateWarehouseService;
use Modules\Warehouse\Application\Services\UpdateWarehouseZoneService;
use Modules\Warehouse\Domain\Entities\Warehouse;
use Modules\Warehouse\Domain\Entities\WarehouseZone;
use Modules\Warehouse\Domain\Events\WarehouseCreated;
use Modules\Warehouse\Domain\Events\WarehouseDeleted;
use Modules\Warehouse\Domain\Events\WarehouseUpdated;
use Modules\Warehouse\Domain\Events\WarehouseZoneCreated;
use Modules\Warehouse\Domain\Events\WarehouseZoneDeleted;
use Modules\Warehouse\Domain\Events\WarehouseZoneUpdated;
use Modules\Warehouse\Domain\Exceptions\WarehouseNotFoundException;
use Modules\Warehouse\Domain\Exceptions\WarehouseZoneNotFoundException;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseRepositoryInterface;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseZoneRepositoryInterface;
use Modules\Warehouse\Infrastructure\Http\Controllers\WarehouseController;
use Modules\Warehouse\Infrastructure\Http\Controllers\WarehouseZoneController;
use Modules\Warehouse\Infrastructure\Http\Requests\StoreWarehouseRequest;
use Modules\Warehouse\Infrastructure\Http\Requests\StoreWarehouseZoneRequest;
use Modules\Warehouse\Infrastructure\Http\Requests\UpdateWarehouseRequest;
use Modules\Warehouse\Infrastructure\Http\Requests\UpdateWarehouseZoneRequest;
use Modules\Warehouse\Infrastructure\Http\Resources\WarehouseCollection;
use Modules\Warehouse\Infrastructure\Http\Resources\WarehouseResource;
use Modules\Warehouse\Infrastructure\Http\Resources\WarehouseZoneCollection;
use Modules\Warehouse\Infrastructure\Http\Resources\WarehouseZoneResource;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseZoneModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories\EloquentWarehouseRepository;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories\EloquentWarehouseZoneRepository;
use Modules\Warehouse\Infrastructure\Providers\WarehouseServiceProvider;
use Modules\Core\Application\Contracts\ReadServiceInterface;
use Modules\Core\Application\Contracts\WriteServiceInterface;
use Modules\Core\Application\DTOs\BaseDto;
use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Core\Domain\Events\BaseEvent;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Domain\ValueObjects\Name;
use PHPUnit\Framework\TestCase;

class WarehouseModuleTest extends TestCase
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    private function createTestWarehouse(int $id = 1, int $tenantId = 1, string $type = 'standard'): Warehouse
    {
        $warehouse = new Warehouse(tenantId: $tenantId, name: new Name('Test Warehouse'), type: $type);
        $ref = new \ReflectionProperty($warehouse, 'id');
        $ref->setAccessible(true);
        $ref->setValue($warehouse, $id);

        return $warehouse;
    }

    private function createTestWarehouseZone(int $id = 1, int $warehouseId = 1, int $tenantId = 1, string $type = 'storage'): WarehouseZone
    {
        $zone = new WarehouseZone(warehouseId: $warehouseId, tenantId: $tenantId, name: new Name('Test Zone'), type: $type);
        $ref = new \ReflectionProperty($zone, 'id');
        $ref->setAccessible(true);
        $ref->setValue($zone, $id);

        return $zone;
    }

    // ── WarehouseNotFoundException ────────────────────────────────────────────

    public function test_warehouse_not_found_exception_class_exists(): void
    {
        $this->assertTrue(class_exists(WarehouseNotFoundException::class));
    }

    public function test_warehouse_not_found_exception_extends_not_found_exception(): void
    {
        $this->assertTrue(
            is_subclass_of(WarehouseNotFoundException::class, NotFoundException::class),
            'WarehouseNotFoundException must extend NotFoundException.'
        );
    }

    public function test_warehouse_not_found_exception_message_contains_id(): void
    {
        $e = new WarehouseNotFoundException(42);
        $this->assertStringContainsString('42', $e->getMessage());
        $this->assertStringContainsString('Warehouse', $e->getMessage());
    }

    public function test_warehouse_not_found_exception_message_without_id(): void
    {
        $e = new WarehouseNotFoundException;
        $this->assertStringContainsString('Warehouse', $e->getMessage());
    }

    // ── WarehouseZoneNotFoundException ────────────────────────────────────────

    public function test_warehouse_zone_not_found_exception_class_exists(): void
    {
        $this->assertTrue(class_exists(WarehouseZoneNotFoundException::class));
    }

    public function test_warehouse_zone_not_found_exception_extends_not_found_exception(): void
    {
        $this->assertTrue(
            is_subclass_of(WarehouseZoneNotFoundException::class, NotFoundException::class),
            'WarehouseZoneNotFoundException must extend NotFoundException.'
        );
    }

    public function test_warehouse_zone_not_found_exception_message_contains_id(): void
    {
        $e = new WarehouseZoneNotFoundException(7);
        $this->assertStringContainsString('7', $e->getMessage());
        $this->assertStringContainsString('WarehouseZone', $e->getMessage());
    }

    // ── Warehouse Entity ──────────────────────────────────────────────────────

    public function test_warehouse_entity_class_exists(): void
    {
        $this->assertTrue(class_exists(Warehouse::class));
    }

    public function test_warehouse_entity_can_be_constructed(): void
    {
        $warehouse = new Warehouse(
            tenantId:    1,
            name:        new Name('Main Warehouse'),
            type:        'standard',
            code:        new Code('WH-001'),
            description: 'Primary distribution warehouse',
            address:     '123 Warehouse St',
            capacity:    1000.0,
            locationId:  5,
            metadata:    new Metadata(['floor_count' => 3]),
            isActive:    true
        );

        $this->assertSame(1, $warehouse->getTenantId());
        $this->assertSame('Main Warehouse', $warehouse->getName()->value());
        $this->assertSame('standard', $warehouse->getType());
        $this->assertSame('WH-001', $warehouse->getCode()->value());
        $this->assertSame('Primary distribution warehouse', $warehouse->getDescription());
        $this->assertSame('123 Warehouse St', $warehouse->getAddress());
        $this->assertSame(1000.0, $warehouse->getCapacity());
        $this->assertSame(5, $warehouse->getLocationId());
        $this->assertTrue($warehouse->isActive());
        $this->assertNull($warehouse->getId());
    }

    public function test_warehouse_entity_minimal_construction(): void
    {
        $warehouse = new Warehouse(tenantId: 2, name: new Name('Cold Storage'), type: 'cold_storage');

        $this->assertNull($warehouse->getId());
        $this->assertSame(2, $warehouse->getTenantId());
        $this->assertNull($warehouse->getCode());
        $this->assertNull($warehouse->getDescription());
        $this->assertNull($warehouse->getAddress());
        $this->assertNull($warehouse->getCapacity());
        $this->assertNull($warehouse->getLocationId());
        $this->assertTrue($warehouse->isActive());
        $this->assertInstanceOf(Metadata::class, $warehouse->getMetadata());
    }

    public function test_warehouse_entity_update_details(): void
    {
        $warehouse = $this->createTestWarehouse();

        $warehouse->updateDetails(
            new Name('Updated Warehouse'),
            'cold_storage',
            new Code('WH-002'),
            'Updated description',
            '456 New Ave',
            2000.0,
            10,
            new Metadata(['temp' => '-18C']),
            false
        );

        $this->assertSame('Updated Warehouse', $warehouse->getName()->value());
        $this->assertSame('cold_storage', $warehouse->getType());
        $this->assertSame('WH-002', $warehouse->getCode()->value());
        $this->assertSame('Updated description', $warehouse->getDescription());
        $this->assertSame('456 New Ave', $warehouse->getAddress());
        $this->assertSame(2000.0, $warehouse->getCapacity());
        $this->assertSame(10, $warehouse->getLocationId());
        $this->assertFalse($warehouse->isActive());
    }

    public function test_warehouse_entity_update_details_clears_nullable_fields(): void
    {
        $warehouse = new Warehouse(
            tenantId:    1,
            name:        new Name('Test'),
            type:        'standard',
            code:        new Code('WH-X'),
            description: 'desc',
            address:     '123 Main St'
        );

        $warehouse->updateDetails(
            new Name('Test'),
            'standard',
            null,
            null,
            null,
            null,
            null,
            null,
            true
        );

        $this->assertNull($warehouse->getCode());
        $this->assertNull($warehouse->getDescription());
        $this->assertNull($warehouse->getAddress());
        $this->assertNull($warehouse->getCapacity());
        $this->assertNull($warehouse->getLocationId());
    }

    public function test_warehouse_entity_activate(): void
    {
        $warehouse = new Warehouse(tenantId: 1, name: new Name('W'), type: 'standard', isActive: false);
        $warehouse->activate();
        $this->assertTrue($warehouse->isActive());
    }

    public function test_warehouse_entity_deactivate(): void
    {
        $warehouse = new Warehouse(tenantId: 1, name: new Name('W'), type: 'standard', isActive: true);
        $warehouse->deactivate();
        $this->assertFalse($warehouse->isActive());
    }

    public function test_warehouse_entity_has_created_at_and_updated_at(): void
    {
        $warehouse = new Warehouse(tenantId: 1, name: new Name('W'), type: 'standard');
        $this->assertInstanceOf(\DateTimeInterface::class, $warehouse->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $warehouse->getUpdatedAt());
    }

    public function test_warehouse_entity_update_details_changes_updated_at(): void
    {
        $before    = new \DateTimeImmutable('2020-01-01');
        $warehouse = new Warehouse(
            tenantId:   1,
            name:       new Name('W'),
            type:       'standard',
            createdAt:  $before,
            updatedAt:  $before
        );

        $warehouse->updateDetails(
            new Name('W2'),
            'standard',
            null,
            null,
            null,
            null,
            null,
            null,
            true
        );

        $this->assertGreaterThan($before->getTimestamp(), $warehouse->getUpdatedAt()->getTimestamp());
    }

    // ── WarehouseZone Entity ──────────────────────────────────────────────────

    public function test_warehouse_zone_entity_class_exists(): void
    {
        $this->assertTrue(class_exists(WarehouseZone::class));
    }

    public function test_warehouse_zone_entity_can_be_constructed(): void
    {
        $zone = new WarehouseZone(
            warehouseId:    1,
            tenantId:       1,
            name:           new Name('Receiving Bay'),
            type:           'receiving',
            code:           new Code('Z-001'),
            description:    'Main receiving dock',
            capacity:       500.0,
            metadata:       new Metadata(['docks' => 3]),
            isActive:       true,
            parentZoneId:   null,
        );

        $this->assertSame(1, $zone->getWarehouseId());
        $this->assertSame(1, $zone->getTenantId());
        $this->assertSame('Receiving Bay', $zone->getName()->value());
        $this->assertSame('receiving', $zone->getType());
        $this->assertSame('Z-001', $zone->getCode()->value());
        $this->assertSame('Main receiving dock', $zone->getDescription());
        $this->assertSame(500.0, $zone->getCapacity());
        $this->assertTrue($zone->isActive());
        $this->assertNull($zone->getParentZoneId());
        $this->assertNull($zone->getId());
    }

    public function test_warehouse_zone_entity_minimal_construction(): void
    {
        $zone = new WarehouseZone(warehouseId: 1, tenantId: 1, name: new Name('Zone A'), type: 'storage');

        $this->assertNull($zone->getId());
        $this->assertNull($zone->getCode());
        $this->assertNull($zone->getDescription());
        $this->assertNull($zone->getCapacity());
        $this->assertTrue($zone->isActive());
        $this->assertNull($zone->getParentZoneId());
        $this->assertInstanceOf(Collection::class, $zone->getChildren());
        $this->assertTrue($zone->getChildren()->isEmpty());
    }

    public function test_warehouse_zone_entity_update_details(): void
    {
        $zone = $this->createTestWarehouseZone();

        $zone->updateDetails(
            new Name('Updated Zone'),
            'picking',
            new Code('Z-UP'),
            'Updated description',
            750.0,
            new Metadata(['rows' => 10]),
            false
        );

        $this->assertSame('Updated Zone', $zone->getName()->value());
        $this->assertSame('picking', $zone->getType());
        $this->assertSame('Z-UP', $zone->getCode()->value());
        $this->assertSame('Updated description', $zone->getDescription());
        $this->assertSame(750.0, $zone->getCapacity());
        $this->assertFalse($zone->isActive());
    }

    public function test_warehouse_zone_entity_set_parent_zone_id(): void
    {
        $zone = $this->createTestWarehouseZone();
        $zone->setParentZoneId(5);
        $this->assertSame(5, $zone->getParentZoneId());
    }

    public function test_warehouse_zone_entity_set_lft_rgt(): void
    {
        $zone = $this->createTestWarehouseZone();
        $zone->setLftRgt(3, 8);
        $this->assertSame(3, $zone->getLft());
        $this->assertSame(8, $zone->getRgt());
    }

    public function test_warehouse_zone_entity_add_child(): void
    {
        $parent = $this->createTestWarehouseZone(1);
        $child  = $this->createTestWarehouseZone(2);

        $parent->addChild($child);

        $this->assertSame(1, $parent->getChildren()->count());
        $this->assertSame(2, $parent->getChildren()->first()->getId());
        $this->assertSame(1, $child->getParentZoneId());
    }

    public function test_warehouse_zone_entity_add_child_idempotent(): void
    {
        $parent = $this->createTestWarehouseZone(1);
        $child  = $this->createTestWarehouseZone(2);

        $parent->addChild($child);
        $parent->addChild($child);

        $this->assertSame(1, $parent->getChildren()->count());
    }

    public function test_warehouse_zone_entity_remove_child(): void
    {
        $parent = $this->createTestWarehouseZone(1);
        $child  = $this->createTestWarehouseZone(2);

        $parent->addChild($child);
        $parent->removeChild($child);

        $this->assertTrue($parent->getChildren()->isEmpty());
        $this->assertNull($child->getParentZoneId());
    }

    public function test_warehouse_zone_entity_has_timestamps(): void
    {
        $zone = new WarehouseZone(warehouseId: 1, tenantId: 1, name: new Name('Z'), type: 'storage');
        $this->assertInstanceOf(\DateTimeInterface::class, $zone->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $zone->getUpdatedAt());
    }

    // ── Domain Events ─────────────────────────────────────────────────────────

    public function test_warehouse_created_event_class_exists(): void
    {
        $this->assertTrue(class_exists(WarehouseCreated::class));
    }

    public function test_warehouse_created_event_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(WarehouseCreated::class, BaseEvent::class));
    }

    public function test_warehouse_created_event_holds_warehouse(): void
    {
        $warehouse = $this->createTestWarehouse(1, 2);
        $event     = new WarehouseCreated($warehouse);

        $this->assertSame($warehouse, $event->warehouse);
        $this->assertSame(2, $event->tenantId);
    }

    public function test_warehouse_updated_event_class_exists(): void
    {
        $this->assertTrue(class_exists(WarehouseUpdated::class));
    }

    public function test_warehouse_updated_event_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(WarehouseUpdated::class, BaseEvent::class));
    }

    public function test_warehouse_deleted_event_class_exists(): void
    {
        $this->assertTrue(class_exists(WarehouseDeleted::class));
    }

    public function test_warehouse_deleted_event_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(WarehouseDeleted::class, BaseEvent::class));
    }

    public function test_warehouse_deleted_event_holds_id_and_tenant_id(): void
    {
        $event = new WarehouseDeleted(42, 3);
        $this->assertSame(42, $event->warehouseId);
        $this->assertSame(3, $event->tenantId);
    }

    public function test_warehouse_zone_created_event_class_exists(): void
    {
        $this->assertTrue(class_exists(WarehouseZoneCreated::class));
    }

    public function test_warehouse_zone_created_event_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(WarehouseZoneCreated::class, BaseEvent::class));
    }

    public function test_warehouse_zone_updated_event_class_exists(): void
    {
        $this->assertTrue(class_exists(WarehouseZoneUpdated::class));
    }

    public function test_warehouse_zone_deleted_event_class_exists(): void
    {
        $this->assertTrue(class_exists(WarehouseZoneDeleted::class));
    }

    public function test_warehouse_zone_deleted_event_holds_id_and_tenant_id(): void
    {
        $event = new WarehouseZoneDeleted(10, 5);
        $this->assertSame(10, $event->zoneId);
        $this->assertSame(5, $event->tenantId);
    }

    // ── Repository Interfaces ─────────────────────────────────────────────────

    public function test_warehouse_repository_interface_exists(): void
    {
        $this->assertTrue(interface_exists(WarehouseRepositoryInterface::class));
    }

    public function test_warehouse_repository_interface_extends_repository_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(WarehouseRepositoryInterface::class, RepositoryInterface::class),
            'WarehouseRepositoryInterface must extend RepositoryInterface.'
        );
    }

    public function test_warehouse_repository_interface_declares_save(): void
    {
        $this->assertTrue(
            method_exists(WarehouseRepositoryInterface::class, 'save'),
            'WarehouseRepositoryInterface must declare save().'
        );
    }

    public function test_warehouse_zone_repository_interface_exists(): void
    {
        $this->assertTrue(interface_exists(WarehouseZoneRepositoryInterface::class));
    }

    public function test_warehouse_zone_repository_interface_extends_repository_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(WarehouseZoneRepositoryInterface::class, RepositoryInterface::class),
            'WarehouseZoneRepositoryInterface must extend RepositoryInterface.'
        );
    }

    public function test_warehouse_zone_repository_interface_declares_get_by_warehouse(): void
    {
        $this->assertTrue(
            method_exists(WarehouseZoneRepositoryInterface::class, 'getByWarehouse'),
            'WarehouseZoneRepositoryInterface must declare getByWarehouse().'
        );
    }

    public function test_warehouse_zone_repository_interface_declares_move_node(): void
    {
        $this->assertTrue(
            method_exists(WarehouseZoneRepositoryInterface::class, 'moveNode'),
            'WarehouseZoneRepositoryInterface must declare moveNode().'
        );
    }

    // ── Service Interfaces ────────────────────────────────────────────────────

    public function test_find_warehouse_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(FindWarehouseServiceInterface::class));
    }

    public function test_find_warehouse_service_interface_extends_read_service_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(FindWarehouseServiceInterface::class, ReadServiceInterface::class),
            'FindWarehouseServiceInterface must extend ReadServiceInterface.'
        );
    }

    public function test_find_warehouse_service_interface_declares_get_by_location(): void
    {
        $this->assertTrue(
            method_exists(FindWarehouseServiceInterface::class, 'getByLocation'),
            'FindWarehouseServiceInterface must declare getByLocation().'
        );
    }

    public function test_create_warehouse_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(CreateWarehouseServiceInterface::class));
    }

    public function test_create_warehouse_service_interface_extends_write_service_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(CreateWarehouseServiceInterface::class, WriteServiceInterface::class),
            'CreateWarehouseServiceInterface must extend WriteServiceInterface.'
        );
    }

    public function test_update_warehouse_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(UpdateWarehouseServiceInterface::class));
    }

    public function test_delete_warehouse_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(DeleteWarehouseServiceInterface::class));
    }

    public function test_find_warehouse_zone_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(FindWarehouseZoneServiceInterface::class));
    }

    public function test_find_warehouse_zone_service_interface_extends_read_service_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(FindWarehouseZoneServiceInterface::class, ReadServiceInterface::class),
            'FindWarehouseZoneServiceInterface must extend ReadServiceInterface.'
        );
    }

    public function test_find_warehouse_zone_service_interface_declares_get_by_warehouse(): void
    {
        $this->assertTrue(
            method_exists(FindWarehouseZoneServiceInterface::class, 'getByWarehouse'),
            'FindWarehouseZoneServiceInterface must declare getByWarehouse().'
        );
    }

    public function test_create_warehouse_zone_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(CreateWarehouseZoneServiceInterface::class));
    }

    public function test_update_warehouse_zone_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(UpdateWarehouseZoneServiceInterface::class));
    }

    public function test_delete_warehouse_zone_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(DeleteWarehouseZoneServiceInterface::class));
    }

    // ── DTOs ──────────────────────────────────────────────────────────────────

    public function test_warehouse_data_class_exists(): void
    {
        $this->assertTrue(class_exists(WarehouseData::class));
    }

    public function test_warehouse_data_extends_base_dto(): void
    {
        $this->assertTrue(is_subclass_of(WarehouseData::class, BaseDto::class));
    }

    public function test_warehouse_data_from_array(): void
    {
        $dto = WarehouseData::fromArray([
            'tenant_id'   => 1,
            'name'        => 'Main WH',
            'type'        => 'standard',
            'code'        => 'WH-001',
            'description' => 'Test',
            'address'     => '123 St',
            'capacity'    => 500.0,
            'location_id' => 3,
            'metadata'    => ['key' => 'val'],
            'is_active'   => true,
        ]);

        $this->assertSame(1, $dto->tenant_id);
        $this->assertSame('Main WH', $dto->name);
        $this->assertSame('standard', $dto->type);
        $this->assertSame('WH-001', $dto->code);
        $this->assertSame('Test', $dto->description);
        $this->assertSame('123 St', $dto->address);
        $this->assertSame(500.0, $dto->capacity);
        $this->assertSame(3, $dto->location_id);
        $this->assertTrue($dto->is_active);
    }

    public function test_warehouse_data_to_array(): void
    {
        $dto = WarehouseData::fromArray([
            'tenant_id' => 2,
            'name'      => 'WH B',
            'type'      => 'bonded',
        ]);

        $arr = $dto->toArray();
        $this->assertArrayHasKey('tenant_id', $arr);
        $this->assertArrayHasKey('name', $arr);
        $this->assertArrayHasKey('type', $arr);
    }

    public function test_update_warehouse_data_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdateWarehouseData::class));
    }

    public function test_update_warehouse_data_extends_base_dto(): void
    {
        $this->assertTrue(is_subclass_of(UpdateWarehouseData::class, BaseDto::class));
    }

    public function test_update_warehouse_data_is_provided_tracks_supplied_keys(): void
    {
        $dto = UpdateWarehouseData::fromArray(['id' => 1, 'name' => 'New Name', 'type' => 'standard']);

        $this->assertTrue($dto->isProvided('name'));
        $this->assertTrue($dto->isProvided('type'));
        $this->assertFalse($dto->isProvided('code'));
        $this->assertFalse($dto->isProvided('description'));
        $this->assertFalse($dto->isProvided('address'));
    }

    public function test_update_warehouse_data_to_array_only_emits_provided_keys(): void
    {
        $dto = UpdateWarehouseData::fromArray(['id' => 5, 'name' => 'Updated']);
        $arr = $dto->toArray();

        $this->assertArrayHasKey('name', $arr);
        $this->assertArrayNotHasKey('code', $arr);
        $this->assertArrayNotHasKey('address', $arr);
    }

    public function test_warehouse_zone_data_class_exists(): void
    {
        $this->assertTrue(class_exists(WarehouseZoneData::class));
    }

    public function test_warehouse_zone_data_extends_base_dto(): void
    {
        $this->assertTrue(is_subclass_of(WarehouseZoneData::class, BaseDto::class));
    }

    public function test_warehouse_zone_data_from_array(): void
    {
        $dto = WarehouseZoneData::fromArray([
            'warehouse_id'   => 1,
            'tenant_id'      => 2,
            'name'           => 'Zone A',
            'type'           => 'storage',
            'code'           => 'Z-001',
            'description'    => 'Storage area',
            'capacity'       => 200.0,
            'metadata'       => ['shelves' => 10],
            'is_active'      => true,
            'parent_zone_id' => null,
        ]);

        $this->assertSame(1, $dto->warehouse_id);
        $this->assertSame(2, $dto->tenant_id);
        $this->assertSame('Zone A', $dto->name);
        $this->assertSame('storage', $dto->type);
    }

    public function test_update_warehouse_zone_data_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdateWarehouseZoneData::class));
    }

    public function test_update_warehouse_zone_data_is_provided_tracks_supplied_keys(): void
    {
        $dto = UpdateWarehouseZoneData::fromArray(['id' => 1, 'name' => 'New Zone', 'type' => 'picking']);

        $this->assertTrue($dto->isProvided('name'));
        $this->assertTrue($dto->isProvided('type'));
        $this->assertFalse($dto->isProvided('code'));
        $this->assertFalse($dto->isProvided('capacity'));
        $this->assertFalse($dto->isProvided('is_active'));
    }

    public function test_update_warehouse_zone_data_to_array_only_emits_provided_keys(): void
    {
        $dto = UpdateWarehouseZoneData::fromArray(['id' => 3, 'type' => 'dispatch']);
        $arr = $dto->toArray();

        $this->assertArrayHasKey('type', $arr);
        $this->assertArrayNotHasKey('name', $arr);
        $this->assertArrayNotHasKey('capacity', $arr);
    }

    // ── FindWarehouseService ──────────────────────────────────────────────────

    public function test_find_warehouse_service_class_exists(): void
    {
        $this->assertTrue(class_exists(FindWarehouseService::class));
    }

    public function test_find_warehouse_service_extends_base_service(): void
    {
        $this->assertTrue(is_subclass_of(FindWarehouseService::class, BaseService::class));
    }

    public function test_find_warehouse_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(FindWarehouseService::class, FindWarehouseServiceInterface::class),
            'FindWarehouseService must implement FindWarehouseServiceInterface.'
        );
    }

    public function test_find_warehouse_service_find_delegates_to_repository(): void
    {
        $warehouse = $this->createTestWarehouse(5);

        $repo = $this->createMock(WarehouseRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('find')
            ->with(5)
            ->willReturn($warehouse);

        $service = new FindWarehouseService($repo);
        $result  = $service->find(5);

        $this->assertSame($warehouse, $result);
    }

    public function test_find_warehouse_service_find_returns_null_when_missing(): void
    {
        $repo = $this->createMock(WarehouseRepositoryInterface::class);
        $repo->method('find')->willReturn(null);

        $service = new FindWarehouseService($repo);
        $this->assertNull($service->find(9999));
    }

    public function test_find_warehouse_service_get_by_location_delegates_to_repository(): void
    {
        $warehouses = [$this->createTestWarehouse(1), $this->createTestWarehouse(2)];

        $repo = $this->createMock(WarehouseRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('getByLocation')
            ->with(3)
            ->willReturn($warehouses);

        $service = new FindWarehouseService($repo);
        $result  = $service->getByLocation(3);

        $this->assertSame($warehouses, $result);
    }

    public function test_find_warehouse_service_handle_throws_bad_method_call_exception(): void
    {
        $this->expectException(\BadMethodCallException::class);

        $repo    = $this->createMock(WarehouseRepositoryInterface::class);
        $service = new FindWarehouseService($repo);

        $method = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $method->invoke($service, []);
    }

    // ── CreateWarehouseService ────────────────────────────────────────────────

    public function test_create_warehouse_service_class_exists(): void
    {
        $this->assertTrue(class_exists(CreateWarehouseService::class));
    }

    public function test_create_warehouse_service_extends_base_service(): void
    {
        $this->assertTrue(is_subclass_of(CreateWarehouseService::class, BaseService::class));
    }

    public function test_create_warehouse_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(CreateWarehouseService::class, CreateWarehouseServiceInterface::class)
        );
    }

    public function test_create_warehouse_service_dispatches_created_event(): void
    {
        $rc     = new \ReflectionClass(CreateWarehouseService::class);
        $source = file_get_contents($rc->getFileName());

        $this->assertStringContainsString('WarehouseCreated', $source,
            'CreateWarehouseService must dispatch WarehouseCreated event.');
    }

    public function test_create_warehouse_service_uses_repository_save(): void
    {
        $rc     = new \ReflectionClass(CreateWarehouseService::class);
        $source = file_get_contents($rc->getFileName());

        $this->assertStringContainsString('->save(', $source,
            'CreateWarehouseService must call repository save().');
    }

    // ── UpdateWarehouseService ────────────────────────────────────────────────

    public function test_update_warehouse_service_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdateWarehouseService::class));
    }

    public function test_update_warehouse_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(UpdateWarehouseService::class, UpdateWarehouseServiceInterface::class)
        );
    }

    public function test_update_warehouse_service_uses_update_dto(): void
    {
        $rc     = new \ReflectionClass(UpdateWarehouseService::class);
        $source = file_get_contents($rc->getFileName());

        $this->assertStringContainsString('UpdateWarehouseData::fromArray', $source,
            'UpdateWarehouseService must build UpdateWarehouseData from the input array.');
    }

    public function test_update_warehouse_service_uses_is_provided(): void
    {
        $rc     = new \ReflectionClass(UpdateWarehouseService::class);
        $source = file_get_contents($rc->getFileName());

        $this->assertStringContainsString('isProvided(', $source,
            'UpdateWarehouseService must use isProvided() for safe partial updates.');
    }

    public function test_update_warehouse_service_throws_when_not_found(): void
    {
        $this->expectException(WarehouseNotFoundException::class);

        $repo = $this->createMock(WarehouseRepositoryInterface::class);
        $repo->method('find')->willReturn(null);

        $service = new UpdateWarehouseService($repo);

        $method = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $method->invoke($service, ['id' => 999, 'name' => 'New Name', 'type' => 'standard']);
    }

    public function test_update_warehouse_service_dispatches_updated_event(): void
    {
        $rc     = new \ReflectionClass(UpdateWarehouseService::class);
        $source = file_get_contents($rc->getFileName());

        $this->assertStringContainsString('WarehouseUpdated', $source,
            'UpdateWarehouseService must dispatch WarehouseUpdated event.');
    }

    // ── DeleteWarehouseService ────────────────────────────────────────────────

    public function test_delete_warehouse_service_class_exists(): void
    {
        $this->assertTrue(class_exists(DeleteWarehouseService::class));
    }

    public function test_delete_warehouse_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(DeleteWarehouseService::class, DeleteWarehouseServiceInterface::class)
        );
    }

    public function test_delete_warehouse_service_throws_when_not_found(): void
    {
        $this->expectException(WarehouseNotFoundException::class);

        $repo = $this->createMock(WarehouseRepositoryInterface::class);
        $repo->method('find')->willReturn(null);

        $service = new DeleteWarehouseService($repo);

        $method = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $method->invoke($service, ['id' => 404]);
    }

    public function test_delete_warehouse_service_dispatches_deleted_event(): void
    {
        $rc     = new \ReflectionClass(DeleteWarehouseService::class);
        $source = file_get_contents($rc->getFileName());

        $this->assertStringContainsString('WarehouseDeleted', $source,
            'DeleteWarehouseService must dispatch WarehouseDeleted event.');
    }

    // ── FindWarehouseZoneService ──────────────────────────────────────────────

    public function test_find_warehouse_zone_service_class_exists(): void
    {
        $this->assertTrue(class_exists(FindWarehouseZoneService::class));
    }

    public function test_find_warehouse_zone_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(FindWarehouseZoneService::class, FindWarehouseZoneServiceInterface::class)
        );
    }

    public function test_find_warehouse_zone_service_get_by_warehouse_delegates_to_repository(): void
    {
        $zones = [$this->createTestWarehouseZone(1), $this->createTestWarehouseZone(2)];

        $repo = $this->createMock(WarehouseZoneRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('getByWarehouse')
            ->with(1)
            ->willReturn($zones);

        $service = new FindWarehouseZoneService($repo);
        $result  = $service->getByWarehouse(1);

        $this->assertSame($zones, $result);
    }

    public function test_find_warehouse_zone_service_handle_throws_bad_method_call_exception(): void
    {
        $this->expectException(\BadMethodCallException::class);

        $repo    = $this->createMock(WarehouseZoneRepositoryInterface::class);
        $service = new FindWarehouseZoneService($repo);

        $method = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $method->invoke($service, []);
    }

    // ── CreateWarehouseZoneService ────────────────────────────────────────────

    public function test_create_warehouse_zone_service_class_exists(): void
    {
        $this->assertTrue(class_exists(CreateWarehouseZoneService::class));
    }

    public function test_create_warehouse_zone_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(CreateWarehouseZoneService::class, CreateWarehouseZoneServiceInterface::class)
        );
    }

    public function test_create_warehouse_zone_service_dispatches_created_event(): void
    {
        $rc     = new \ReflectionClass(CreateWarehouseZoneService::class);
        $source = file_get_contents($rc->getFileName());

        $this->assertStringContainsString('WarehouseZoneCreated', $source,
            'CreateWarehouseZoneService must dispatch WarehouseZoneCreated event.');
    }

    // ── UpdateWarehouseZoneService ────────────────────────────────────────────

    public function test_update_warehouse_zone_service_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdateWarehouseZoneService::class));
    }

    public function test_update_warehouse_zone_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(UpdateWarehouseZoneService::class, UpdateWarehouseZoneServiceInterface::class)
        );
    }

    public function test_update_warehouse_zone_service_uses_is_provided(): void
    {
        $rc     = new \ReflectionClass(UpdateWarehouseZoneService::class);
        $source = file_get_contents($rc->getFileName());

        $this->assertStringContainsString('isProvided(', $source,
            'UpdateWarehouseZoneService must use isProvided() for safe partial updates.');
    }

    public function test_update_warehouse_zone_service_throws_when_not_found(): void
    {
        $this->expectException(WarehouseZoneNotFoundException::class);

        $repo = $this->createMock(WarehouseZoneRepositoryInterface::class);
        $repo->method('find')->willReturn(null);

        $service = new UpdateWarehouseZoneService($repo);

        $method = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $method->invoke($service, ['id' => 999, 'name' => 'Z', 'type' => 'storage']);
    }

    // ── DeleteWarehouseZoneService ────────────────────────────────────────────

    public function test_delete_warehouse_zone_service_class_exists(): void
    {
        $this->assertTrue(class_exists(DeleteWarehouseZoneService::class));
    }

    public function test_delete_warehouse_zone_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(DeleteWarehouseZoneService::class, DeleteWarehouseZoneServiceInterface::class)
        );
    }

    public function test_delete_warehouse_zone_service_throws_when_not_found(): void
    {
        $this->expectException(WarehouseZoneNotFoundException::class);

        $repo = $this->createMock(WarehouseZoneRepositoryInterface::class);
        $repo->method('find')->willReturn(null);

        $service = new DeleteWarehouseZoneService($repo);

        $method = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $method->invoke($service, ['id' => 404]);
    }

    // ── HTTP Controllers ──────────────────────────────────────────────────────

    public function test_warehouse_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(WarehouseController::class));
    }

    public function test_warehouse_controller_extends_authorized_controller(): void
    {
        $this->assertTrue(
            is_subclass_of(
                WarehouseController::class,
                \Modules\Core\Infrastructure\Http\Controllers\AuthorizedController::class
            ),
            'WarehouseController must extend AuthorizedController.'
        );
    }

    public function test_warehouse_controller_injects_find_service(): void
    {
        $rc = new \ReflectionClass(WarehouseController::class);
        $source = file_get_contents($rc->getFileName());

        $this->assertStringContainsString('FindWarehouseServiceInterface', $source);
    }

    public function test_warehouse_controller_injects_create_service(): void
    {
        $rc = new \ReflectionClass(WarehouseController::class);
        $source = file_get_contents($rc->getFileName());

        $this->assertStringContainsString('CreateWarehouseServiceInterface', $source);
    }

    public function test_warehouse_controller_injects_update_service(): void
    {
        $rc = new \ReflectionClass(WarehouseController::class);
        $source = file_get_contents($rc->getFileName());

        $this->assertStringContainsString('UpdateWarehouseServiceInterface', $source);
    }

    public function test_warehouse_controller_injects_delete_service(): void
    {
        $rc = new \ReflectionClass(WarehouseController::class);
        $source = file_get_contents($rc->getFileName());

        $this->assertStringContainsString('DeleteWarehouseServiceInterface', $source);
    }

    public function test_warehouse_controller_has_index_method(): void
    {
        $this->assertTrue(method_exists(WarehouseController::class, 'index'));
    }

    public function test_warehouse_controller_has_store_method(): void
    {
        $this->assertTrue(method_exists(WarehouseController::class, 'store'));
    }

    public function test_warehouse_controller_has_show_method(): void
    {
        $this->assertTrue(method_exists(WarehouseController::class, 'show'));
    }

    public function test_warehouse_controller_has_update_method(): void
    {
        $this->assertTrue(method_exists(WarehouseController::class, 'update'));
    }

    public function test_warehouse_controller_has_destroy_method(): void
    {
        $this->assertTrue(method_exists(WarehouseController::class, 'destroy'));
    }

    public function test_warehouse_controller_has_by_location_method(): void
    {
        $this->assertTrue(method_exists(WarehouseController::class, 'byLocation'));
    }

    public function test_warehouse_zone_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(WarehouseZoneController::class));
    }

    public function test_warehouse_zone_controller_extends_authorized_controller(): void
    {
        $this->assertTrue(
            is_subclass_of(
                WarehouseZoneController::class,
                \Modules\Core\Infrastructure\Http\Controllers\AuthorizedController::class
            ),
            'WarehouseZoneController must extend AuthorizedController.'
        );
    }

    public function test_warehouse_zone_controller_has_crud_methods(): void
    {
        $this->assertTrue(method_exists(WarehouseZoneController::class, 'index'));
        $this->assertTrue(method_exists(WarehouseZoneController::class, 'store'));
        $this->assertTrue(method_exists(WarehouseZoneController::class, 'show'));
        $this->assertTrue(method_exists(WarehouseZoneController::class, 'update'));
        $this->assertTrue(method_exists(WarehouseZoneController::class, 'destroy'));
    }

    // ── HTTP Requests ─────────────────────────────────────────────────────────

    public function test_store_warehouse_request_class_exists(): void
    {
        $this->assertTrue(class_exists(StoreWarehouseRequest::class));
    }

    public function test_store_warehouse_request_has_rules(): void
    {
        $req   = new StoreWarehouseRequest;
        $rules = $req->rules();

        $this->assertArrayHasKey('tenant_id', $rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('type', $rules);
        $this->assertStringContainsString('required', $rules['tenant_id']);
        $this->assertStringContainsString('required', $rules['name']);
        $this->assertStringContainsString('required', $rules['type']);
    }

    public function test_update_warehouse_request_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdateWarehouseRequest::class));
    }

    public function test_update_warehouse_request_uses_sometimes_required(): void
    {
        $req   = new UpdateWarehouseRequest;
        $rules = $req->rules();

        $this->assertStringContainsString('sometimes', $rules['name']);
        $this->assertStringContainsString('sometimes', $rules['type']);
    }

    public function test_store_warehouse_zone_request_class_exists(): void
    {
        $this->assertTrue(class_exists(StoreWarehouseZoneRequest::class));
    }

    public function test_store_warehouse_zone_request_has_rules(): void
    {
        $req   = new StoreWarehouseZoneRequest;
        $rules = $req->rules();

        $this->assertArrayHasKey('tenant_id', $rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('type', $rules);
    }

    public function test_update_warehouse_zone_request_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdateWarehouseZoneRequest::class));
    }

    public function test_update_warehouse_zone_request_uses_sometimes_required(): void
    {
        $req   = new UpdateWarehouseZoneRequest;
        $rules = $req->rules();

        $this->assertStringContainsString('sometimes', $rules['name']);
        $this->assertStringContainsString('sometimes', $rules['type']);
    }

    // ── HTTP Resources ────────────────────────────────────────────────────────

    public function test_warehouse_resource_class_exists(): void
    {
        $this->assertTrue(class_exists(WarehouseResource::class));
    }

    public function test_warehouse_collection_class_exists(): void
    {
        $this->assertTrue(class_exists(WarehouseCollection::class));
    }

    public function test_warehouse_collection_collects_warehouse_resource(): void
    {
        $rc = new \ReflectionClass(WarehouseCollection::class);
        $source = file_get_contents($rc->getFileName());

        $this->assertStringContainsString('WarehouseResource', $source,
            'WarehouseCollection must reference WarehouseResource.');
    }

    public function test_warehouse_zone_resource_class_exists(): void
    {
        $this->assertTrue(class_exists(WarehouseZoneResource::class));
    }

    public function test_warehouse_zone_collection_class_exists(): void
    {
        $this->assertTrue(class_exists(WarehouseZoneCollection::class));
    }

    public function test_warehouse_resource_to_array_contains_expected_keys(): void
    {
        $warehouse = new Warehouse(
            tenantId:  1,
            name:      new Name('WH'),
            type:      'standard',
            code:      new Code('WH-1'),
            isActive:  true,
            id:        1,
        );

        // Inject id via reflection
        $ref = new \ReflectionProperty($warehouse, 'id');
        $ref->setAccessible(true);
        $ref->setValue($warehouse, 1);

        $resource = new WarehouseResource($warehouse);
        $arr      = $resource->toArray(new \Illuminate\Http\Request);

        $this->assertArrayHasKey('id', $arr);
        $this->assertArrayHasKey('tenant_id', $arr);
        $this->assertArrayHasKey('name', $arr);
        $this->assertArrayHasKey('type', $arr);
        $this->assertArrayHasKey('code', $arr);
        $this->assertArrayHasKey('is_active', $arr);
        $this->assertArrayHasKey('created_at', $arr);
        $this->assertArrayHasKey('updated_at', $arr);
        $this->assertSame('WH', $arr['name']);
        $this->assertSame('WH-1', $arr['code']);
        $this->assertTrue($arr['is_active']);
    }

    public function test_warehouse_zone_resource_to_array_contains_expected_keys(): void
    {
        $zone = new WarehouseZone(
            warehouseId: 1,
            tenantId:    1,
            name:        new Name('Zone B'),
            type:        'packing',
        );

        $ref = new \ReflectionProperty($zone, 'id');
        $ref->setAccessible(true);
        $ref->setValue($zone, 2);

        $resource = new WarehouseZoneResource($zone);
        $arr      = $resource->toArray(new \Illuminate\Http\Request);

        $this->assertArrayHasKey('id', $arr);
        $this->assertArrayHasKey('warehouse_id', $arr);
        $this->assertArrayHasKey('tenant_id', $arr);
        $this->assertArrayHasKey('name', $arr);
        $this->assertArrayHasKey('type', $arr);
        $this->assertArrayHasKey('is_active', $arr);
        $this->assertSame('Zone B', $arr['name']);
        $this->assertSame('packing', $arr['type']);
    }

    // ── Eloquent Models ───────────────────────────────────────────────────────

    public function test_warehouse_model_class_exists(): void
    {
        $this->assertTrue(class_exists(WarehouseModel::class));
    }

    public function test_warehouse_model_uses_soft_deletes(): void
    {
        $uses = class_uses_recursive(WarehouseModel::class);
        $this->assertContains(\Illuminate\Database\Eloquent\SoftDeletes::class, $uses,
            'WarehouseModel must use SoftDeletes.');
    }

    public function test_warehouse_model_has_expected_fillable_fields(): void
    {
        $model    = new WarehouseModel;
        $fillable = $model->getFillable();

        $this->assertContains('tenant_id', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('type', $fillable);
        $this->assertContains('code', $fillable);
        $this->assertContains('is_active', $fillable);
    }

    public function test_warehouse_zone_model_class_exists(): void
    {
        $this->assertTrue(class_exists(WarehouseZoneModel::class));
    }

    public function test_warehouse_zone_model_uses_soft_deletes(): void
    {
        $uses = class_uses_recursive(WarehouseZoneModel::class);
        $this->assertContains(\Illuminate\Database\Eloquent\SoftDeletes::class, $uses,
            'WarehouseZoneModel must use SoftDeletes.');
    }

    public function test_warehouse_zone_model_has_nested_set_columns_in_fillable(): void
    {
        $model    = new WarehouseZoneModel;
        $fillable = $model->getFillable();

        $this->assertContains('_lft', $fillable);
        $this->assertContains('_rgt', $fillable);
        $this->assertContains('parent_zone_id', $fillable);
    }

    public function test_warehouse_zone_model_has_get_descendants_method(): void
    {
        $this->assertTrue(method_exists(WarehouseZoneModel::class, 'getDescendants'));
    }

    public function test_warehouse_zone_model_has_get_ancestors_method(): void
    {
        $this->assertTrue(method_exists(WarehouseZoneModel::class, 'getAncestors'));
    }

    // ── Eloquent Repositories ─────────────────────────────────────────────────

    public function test_eloquent_warehouse_repository_class_exists(): void
    {
        $this->assertTrue(class_exists(EloquentWarehouseRepository::class));
    }

    public function test_eloquent_warehouse_repository_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(EloquentWarehouseRepository::class, WarehouseRepositoryInterface::class)
        );
    }

    public function test_eloquent_warehouse_repository_has_save_method(): void
    {
        $this->assertTrue(method_exists(EloquentWarehouseRepository::class, 'save'));
    }

    public function test_eloquent_warehouse_zone_repository_class_exists(): void
    {
        $this->assertTrue(class_exists(EloquentWarehouseZoneRepository::class));
    }

    public function test_eloquent_warehouse_zone_repository_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(EloquentWarehouseZoneRepository::class, WarehouseZoneRepositoryInterface::class)
        );
    }

    public function test_eloquent_warehouse_zone_repository_has_move_node_method(): void
    {
        $this->assertTrue(method_exists(EloquentWarehouseZoneRepository::class, 'moveNode'));
    }

    public function test_eloquent_warehouse_zone_repository_uses_nested_set_logic(): void
    {
        $rc     = new \ReflectionClass(EloquentWarehouseZoneRepository::class);
        $source = file_get_contents($rc->getFileName());

        $this->assertStringContainsString('_lft', $source,
            'EloquentWarehouseZoneRepository must implement nested-set logic (_lft).');
        $this->assertStringContainsString('_rgt', $source,
            'EloquentWarehouseZoneRepository must implement nested-set logic (_rgt).');
    }

    // ── Service Provider ──────────────────────────────────────────────────────

    public function test_warehouse_service_provider_class_exists(): void
    {
        $this->assertTrue(class_exists(WarehouseServiceProvider::class));
    }

    public function test_warehouse_service_provider_extends_service_provider(): void
    {
        $this->assertTrue(
            is_subclass_of(WarehouseServiceProvider::class, \Illuminate\Support\ServiceProvider::class),
            'WarehouseServiceProvider must extend Illuminate ServiceProvider.'
        );
    }

    public function test_warehouse_service_provider_binds_all_interfaces(): void
    {
        $rc     = new \ReflectionClass(WarehouseServiceProvider::class);
        $source = file_get_contents($rc->getFileName());

        $this->assertStringContainsString('WarehouseRepositoryInterface::class', $source);
        $this->assertStringContainsString('FindWarehouseServiceInterface::class', $source);
        $this->assertStringContainsString('CreateWarehouseServiceInterface::class', $source);
        $this->assertStringContainsString('UpdateWarehouseServiceInterface::class', $source);
        $this->assertStringContainsString('DeleteWarehouseServiceInterface::class', $source);
        $this->assertStringContainsString('WarehouseZoneRepositoryInterface::class', $source);
        $this->assertStringContainsString('FindWarehouseZoneServiceInterface::class', $source);
        $this->assertStringContainsString('CreateWarehouseZoneServiceInterface::class', $source);
        $this->assertStringContainsString('UpdateWarehouseZoneServiceInterface::class', $source);
        $this->assertStringContainsString('DeleteWarehouseZoneServiceInterface::class', $source);
    }

    public function test_warehouse_service_provider_loads_routes(): void
    {
        $rc     = new \ReflectionClass(WarehouseServiceProvider::class);
        $source = file_get_contents($rc->getFileName());

        $this->assertStringContainsString('loadRoutesFrom', $source);
    }

    public function test_warehouse_service_provider_loads_migrations(): void
    {
        $rc     = new \ReflectionClass(WarehouseServiceProvider::class);
        $source = file_get_contents($rc->getFileName());

        $this->assertStringContainsString('loadMigrationsFrom', $source);
    }

    public function test_warehouse_service_provider_is_registered_in_bootstrap_providers(): void
    {
        $providersFile = __DIR__.'/../../bootstrap/providers.php';
        $providers     = require $providersFile;

        $this->assertContains(
            WarehouseServiceProvider::class,
            $providers,
            'WarehouseServiceProvider must be registered in bootstrap/providers.php.'
        );
    }

    // ── Routes ────────────────────────────────────────────────────────────────

    public function test_warehouse_routes_file_exists(): void
    {
        $path = __DIR__.'/../../app/Modules/Warehouse/routes/api.php';
        $this->assertFileExists($path);
    }

    public function test_warehouse_routes_contains_warehouses_resource(): void
    {
        $source = file_get_contents(__DIR__.'/../../app/Modules/Warehouse/routes/api.php');
        $this->assertStringContainsString('warehouses', $source);
    }

    public function test_warehouse_routes_contains_by_location_route(): void
    {
        $source = file_get_contents(__DIR__.'/../../app/Modules/Warehouse/routes/api.php');
        $this->assertStringContainsString('by-location', $source);
    }

    public function test_warehouse_routes_contains_zones_nested_resource(): void
    {
        $source = file_get_contents(__DIR__.'/../../app/Modules/Warehouse/routes/api.php');
        $this->assertStringContainsString('zones', $source);
    }

    // ── Database Migrations ───────────────────────────────────────────────────

    public function test_warehouses_migration_file_exists(): void
    {
        $files = glob(__DIR__.'/../../app/Modules/Warehouse/database/migrations/*create_warehouses_table*');
        $this->assertNotEmpty($files, 'warehouses migration file must exist.');
    }

    public function test_warehouse_zones_migration_file_exists(): void
    {
        $files = glob(__DIR__.'/../../app/Modules/Warehouse/database/migrations/*create_warehouse_zones_table*');
        $this->assertNotEmpty($files, 'warehouse_zones migration file must exist.');
    }

    public function test_warehouses_migration_creates_expected_columns(): void
    {
        $files  = glob(__DIR__.'/../../app/Modules/Warehouse/database/migrations/*create_warehouses_table*');
        $source = file_get_contents($files[0]);

        $this->assertStringContainsString('tenant_id', $source);
        $this->assertStringContainsString('name', $source);
        $this->assertStringContainsString('type', $source);
        $this->assertStringContainsString('code', $source);
        $this->assertStringContainsString('is_active', $source);
        $this->assertStringContainsString('softDeletes', $source);
    }

    public function test_warehouse_zones_migration_creates_nested_set_columns(): void
    {
        $files  = glob(__DIR__.'/../../app/Modules/Warehouse/database/migrations/*create_warehouse_zones_table*');
        $source = file_get_contents($files[0]);

        $this->assertStringContainsString('_lft', $source);
        $this->assertStringContainsString('_rgt', $source);
        $this->assertStringContainsString('parent_zone_id', $source);
        $this->assertStringContainsString('warehouse_id', $source);
    }
}
