<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Support\Collection;
use Modules\Location\Application\Contracts\CreateLocationServiceInterface;
use Modules\Location\Application\Contracts\DeleteLocationServiceInterface;
use Modules\Location\Application\Contracts\FindLocationServiceInterface;
use Modules\Location\Application\Contracts\MoveLocationServiceInterface;
use Modules\Location\Application\Contracts\UpdateLocationServiceInterface;
use Modules\Location\Application\DTOs\LocationData;
use Modules\Location\Application\DTOs\MoveLocationData;
use Modules\Location\Application\DTOs\UpdateLocationData;
use Modules\Location\Application\Services\CreateLocationService;
use Modules\Location\Application\Services\DeleteLocationService;
use Modules\Location\Application\Services\FindLocationService;
use Modules\Location\Application\Services\MoveLocationService;
use Modules\Location\Application\Services\UpdateLocationService;
use Modules\Location\Domain\Entities\Location;
use Modules\Location\Domain\Events\LocationCreated;
use Modules\Location\Domain\Events\LocationDeleted;
use Modules\Location\Domain\Events\LocationMoved;
use Modules\Location\Domain\Events\LocationUpdated;
use Modules\Location\Domain\Exceptions\LocationNotFoundException;
use Modules\Location\Domain\RepositoryInterfaces\LocationRepositoryInterface;
use Modules\Location\Infrastructure\Http\Controllers\LocationController;
use Modules\Location\Infrastructure\Http\Requests\MoveLocationRequest;
use Modules\Location\Infrastructure\Http\Requests\StoreLocationRequest;
use Modules\Location\Infrastructure\Http\Requests\UpdateLocationRequest;
use Modules\Location\Infrastructure\Http\Resources\LocationCollection;
use Modules\Location\Infrastructure\Http\Resources\LocationResource;
use Modules\Location\Infrastructure\Http\Resources\LocationTreeResource;
use Modules\Location\Infrastructure\Persistence\Eloquent\Models\LocationModel;
use Modules\Location\Infrastructure\Persistence\Eloquent\Repositories\EloquentLocationRepository;
use Modules\Location\Infrastructure\Providers\LocationServiceProvider;
use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Domain\ValueObjects\Name;
use PHPUnit\Framework\TestCase;

class LocationModuleTest extends TestCase
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    private function createTestLocation(int $id = 1, int $tenantId = 1, string $type = 'country'): Location
    {
        $location = new Location(tenantId: $tenantId, name: new Name('Test Location'), type: $type);
        $ref      = new \ReflectionProperty($location, 'id');
        $ref->setAccessible(true);
        $ref->setValue($location, $id);

        return $location;
    }

    // ── LocationNotFoundException ─────────────────────────────────────────────

    public function test_location_not_found_exception_class_exists(): void
    {
        $this->assertTrue(class_exists(LocationNotFoundException::class));
    }

    public function test_location_not_found_exception_extends_not_found_exception(): void
    {
        $this->assertTrue(
            is_subclass_of(LocationNotFoundException::class, \Modules\Core\Domain\Exceptions\NotFoundException::class),
            'LocationNotFoundException must extend NotFoundException.'
        );
    }

    public function test_location_not_found_exception_message_contains_id(): void
    {
        $e = new LocationNotFoundException(42);
        $this->assertStringContainsString('42', $e->getMessage());
        $this->assertStringContainsString('Location', $e->getMessage());
    }

    public function test_location_not_found_exception_message_without_id(): void
    {
        $e = new LocationNotFoundException;
        $this->assertStringContainsString('Location', $e->getMessage());
    }

    // ── Location Entity ───────────────────────────────────────────────────────

    public function test_location_entity_class_exists(): void
    {
        $this->assertTrue(class_exists(Location::class));
    }

    public function test_location_entity_can_be_constructed(): void
    {
        $location = new Location(
            tenantId:    1,
            name:        new Name('United States'),
            type:        'country',
            code:        new Code('US'),
            description: 'North American country',
            latitude:    37.09024,
            longitude:   -95.712891,
            timezone:    'America/New_York',
            metadata:    new Metadata(['population' => 331000000]),
            parentId:    null
        );

        $this->assertSame(1, $location->getTenantId());
        $this->assertSame('United States', $location->getName()->value());
        $this->assertSame('country', $location->getType());
        $this->assertSame('US', $location->getCode()->value());
        $this->assertSame('North American country', $location->getDescription());
        $this->assertSame(37.09024, $location->getLatitude());
        $this->assertSame(-95.712891, $location->getLongitude());
        $this->assertSame('America/New_York', $location->getTimezone());
        $this->assertNull($location->getParentId());
    }

    public function test_location_entity_minimal_construction(): void
    {
        $location = new Location(tenantId: 1, name: new Name('Country'), type: 'country');

        $this->assertNull($location->getId());
        $this->assertNull($location->getCode());
        $this->assertNull($location->getDescription());
        $this->assertNull($location->getLatitude());
        $this->assertNull($location->getLongitude());
        $this->assertNull($location->getTimezone());
        $this->assertNull($location->getParentId());
        $this->assertInstanceOf(Collection::class, $location->getChildren());
        $this->assertTrue($location->getChildren()->isEmpty());
    }

    public function test_location_entity_update_details(): void
    {
        $location = $this->createTestLocation();

        $location->updateDetails(
            new Name('Updated Name'),
            'state',
            new Code('CA'),
            'California state',
            34.0522,
            -118.2437,
            'America/Los_Angeles',
            new Metadata(['region' => 'West'])
        );

        $this->assertSame('Updated Name', $location->getName()->value());
        $this->assertSame('state', $location->getType());
        $this->assertSame('CA', $location->getCode()->value());
        $this->assertSame('California state', $location->getDescription());
        $this->assertSame(34.0522, $location->getLatitude());
        $this->assertSame(-118.2437, $location->getLongitude());
        $this->assertSame('America/Los_Angeles', $location->getTimezone());
    }

    public function test_location_entity_update_details_clears_nullable_fields(): void
    {
        $location = new Location(
            tenantId: 1,
            name:     new Name('Test'),
            type:     'country',
            code:     new Code('US'),
            timezone: 'UTC'
        );

        $location->updateDetails(
            new Name('Test'),
            'country',
            null,
            null,
            null,
            null,
            null,
            null
        );

        $this->assertNull($location->getCode());
        $this->assertNull($location->getTimezone());
        $this->assertNull($location->getLatitude());
        $this->assertNull($location->getLongitude());
    }

    public function test_location_entity_add_child(): void
    {
        $parent = $this->createTestLocation(1);
        $child  = $this->createTestLocation(2);

        $parent->addChild($child);

        $this->assertSame(1, $parent->getChildren()->count());
        $this->assertSame(2, $parent->getChildren()->first()->getId());
        $this->assertSame(1, $child->getParentId());
    }

    public function test_location_entity_add_child_idempotent(): void
    {
        $parent = $this->createTestLocation(1);
        $child  = $this->createTestLocation(2);

        $parent->addChild($child);
        $parent->addChild($child);

        $this->assertSame(1, $parent->getChildren()->count());
    }

    public function test_location_entity_remove_child(): void
    {
        $parent = $this->createTestLocation(1);
        $child  = $this->createTestLocation(2);

        $parent->addChild($child);
        $parent->removeChild($child);

        $this->assertTrue($parent->getChildren()->isEmpty());
        $this->assertNull($child->getParentId());
    }

    public function test_location_entity_set_parent_id(): void
    {
        $location = $this->createTestLocation(1);
        $location->setParentId(5);

        $this->assertSame(5, $location->getParentId());
    }

    public function test_location_entity_set_lft_rgt(): void
    {
        $location = $this->createTestLocation(1);
        $location->setLftRgt(3, 8);

        $this->assertSame(3, $location->getLft());
        $this->assertSame(8, $location->getRgt());
    }

    public function test_location_entity_has_created_at_and_updated_at(): void
    {
        $location = $this->createTestLocation(1);

        $this->assertInstanceOf(\DateTimeInterface::class, $location->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $location->getUpdatedAt());
    }

    // ── Domain Events ─────────────────────────────────────────────────────────

    public function test_location_created_event_class_exists(): void
    {
        $this->assertTrue(class_exists(LocationCreated::class));
    }

    public function test_location_created_event_stores_location(): void
    {
        $location = $this->createTestLocation();
        $event    = new LocationCreated($location);

        $this->assertSame($location, $event->location);
    }

    public function test_location_updated_event_class_exists(): void
    {
        $this->assertTrue(class_exists(LocationUpdated::class));
    }

    public function test_location_updated_event_stores_location(): void
    {
        $location = $this->createTestLocation();
        $event    = new LocationUpdated($location);

        $this->assertSame($location, $event->location);
    }

    public function test_location_deleted_event_class_exists(): void
    {
        $this->assertTrue(class_exists(LocationDeleted::class));
    }

    public function test_location_deleted_event_stores_id_and_tenant(): void
    {
        $event = new LocationDeleted(42, 7);

        $this->assertSame(42, $event->locationId);
    }

    public function test_location_moved_event_class_exists(): void
    {
        $this->assertTrue(class_exists(LocationMoved::class));
    }

    public function test_location_moved_event_stores_location_and_old_parent(): void
    {
        $location = $this->createTestLocation();
        $event    = new LocationMoved($location, 5);

        $this->assertSame($location, $event->location);
        $this->assertSame(5, $event->oldParentId);
    }

    // ── LocationRepositoryInterface ───────────────────────────────────────────

    public function test_location_repository_interface_exists(): void
    {
        $this->assertTrue(interface_exists(LocationRepositoryInterface::class));
    }

    public function test_location_repository_interface_declares_required_methods(): void
    {
        $rc = new \ReflectionClass(LocationRepositoryInterface::class);

        $this->assertTrue($rc->hasMethod('save'));
        $this->assertTrue($rc->hasMethod('getTree'));
        $this->assertTrue($rc->hasMethod('getDescendants'));
        $this->assertTrue($rc->hasMethod('getAncestors'));
        $this->assertTrue($rc->hasMethod('moveNode'));
        $this->assertTrue($rc->hasMethod('rebuildTree'));
    }

    // ── CreateLocationServiceInterface ────────────────────────────────────────

    public function test_create_location_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(CreateLocationServiceInterface::class));
    }

    public function test_create_location_service_interface_extends_write_service_interface(): void
    {
        $rc = new \ReflectionClass(CreateLocationServiceInterface::class);

        $this->assertTrue(
            $rc->implementsInterface(\Modules\Core\Application\Contracts\WriteServiceInterface::class),
            'CreateLocationServiceInterface must extend WriteServiceInterface.'
        );
    }

    // ── CreateLocationService ─────────────────────────────────────────────────

    public function test_create_location_service_class_exists(): void
    {
        $this->assertTrue(class_exists(CreateLocationService::class));
    }

    public function test_create_location_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(CreateLocationService::class, CreateLocationServiceInterface::class),
            'CreateLocationService must implement CreateLocationServiceInterface.'
        );
    }

    public function test_create_location_service_uses_dto(): void
    {
        $rc     = new \ReflectionClass(CreateLocationService::class);
        $source = file_get_contents($rc->getFileName());

        $this->assertStringContainsString('LocationData::fromArray', $source,
            'CreateLocationService must use LocationData DTO.');
    }

    public function test_create_location_service_uses_repository_save(): void
    {
        $rc     = new \ReflectionClass(CreateLocationService::class);
        $source = file_get_contents($rc->getFileName());

        $this->assertStringContainsString('save', $source,
            'CreateLocationService must call repository save().');
    }

    public function test_create_location_service_dispatches_created_event(): void
    {
        $rc     = new \ReflectionClass(CreateLocationService::class);
        $source = file_get_contents($rc->getFileName());

        $this->assertStringContainsString('LocationCreated', $source,
            'CreateLocationService must dispatch LocationCreated event.');
    }

    // ── FindLocationServiceInterface ──────────────────────────────────────────

    public function test_find_location_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(FindLocationServiceInterface::class));
    }

    public function test_find_location_service_interface_declares_required_methods(): void
    {
        $rc = new \ReflectionClass(FindLocationServiceInterface::class);

        $this->assertTrue($rc->hasMethod('find'));
        $this->assertTrue($rc->hasMethod('list'));
        $this->assertTrue($rc->hasMethod('getTree'));
        $this->assertTrue($rc->hasMethod('getDescendants'));
        $this->assertTrue($rc->hasMethod('getAncestors'));
    }

    public function test_find_location_service_interface_extends_read_service_interface(): void
    {
        $rc = new \ReflectionClass(FindLocationServiceInterface::class);

        $this->assertTrue(
            $rc->implementsInterface(\Modules\Core\Application\Contracts\ReadServiceInterface::class),
            'FindLocationServiceInterface must extend ReadServiceInterface.'
        );
    }

    // ── FindLocationService ───────────────────────────────────────────────────

    public function test_find_location_service_class_exists(): void
    {
        $this->assertTrue(class_exists(FindLocationService::class));
    }

    public function test_find_location_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(FindLocationService::class, FindLocationServiceInterface::class),
            'FindLocationService must implement FindLocationServiceInterface.'
        );
    }

    public function test_find_location_service_find_delegates_to_repository(): void
    {
        $location = $this->createTestLocation(7);

        $repo = $this->createMock(LocationRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('find')
            ->with(7)
            ->willReturn($location);

        $service = new FindLocationService($repo);
        $result  = $service->find(7);

        $this->assertSame($location, $result);
    }

    public function test_find_location_service_find_returns_null_when_missing(): void
    {
        $repo = $this->createMock(LocationRepositoryInterface::class);
        $repo->method('find')->willReturn(null);

        $service = new FindLocationService($repo);
        $this->assertNull($service->find(9999));
    }

    public function test_find_location_service_get_tree_delegates_to_repository(): void
    {
        $tree = [['id' => 1, 'name' => 'Root', 'children' => []]];

        $repo = $this->createMock(LocationRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('getTree')
            ->with(1, null)
            ->willReturn($tree);

        $service = new FindLocationService($repo);
        $result  = $service->getTree(1, null);

        $this->assertSame($tree, $result);
    }

    public function test_find_location_service_get_tree_with_root_id(): void
    {
        $tree = [['id' => 5, 'name' => 'Sub', 'children' => []]];

        $repo = $this->createMock(LocationRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('getTree')
            ->with(2, 5)
            ->willReturn($tree);

        $service = new FindLocationService($repo);
        $result  = $service->getTree(2, 5);

        $this->assertSame($tree, $result);
    }

    public function test_find_location_service_get_descendants_delegates_to_repository(): void
    {
        $descendants = [$this->createTestLocation(2), $this->createTestLocation(3)];

        $repo = $this->createMock(LocationRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('getDescendants')
            ->with(1)
            ->willReturn($descendants);

        $service = new FindLocationService($repo);
        $result  = $service->getDescendants(1);

        $this->assertSame($descendants, $result);
    }

    public function test_find_location_service_get_ancestors_delegates_to_repository(): void
    {
        $ancestors = [$this->createTestLocation(10)];

        $repo = $this->createMock(LocationRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('getAncestors')
            ->with(5)
            ->willReturn($ancestors);

        $service = new FindLocationService($repo);
        $result  = $service->getAncestors(5);

        $this->assertSame($ancestors, $result);
    }

    public function test_find_location_service_handle_throws_bad_method_call_exception(): void
    {
        $this->expectException(\BadMethodCallException::class);

        $repo    = $this->createMock(LocationRepositoryInterface::class);
        $service = new FindLocationService($repo);

        $method = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $method->invoke($service, []);
    }

    // ── UpdateLocationServiceInterface ────────────────────────────────────────

    public function test_update_location_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(UpdateLocationServiceInterface::class));
    }

    // ── UpdateLocationService ─────────────────────────────────────────────────

    public function test_update_location_service_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdateLocationService::class));
    }

    public function test_update_location_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(UpdateLocationService::class, UpdateLocationServiceInterface::class),
            'UpdateLocationService must implement UpdateLocationServiceInterface.'
        );
    }

    public function test_update_location_service_uses_update_dto(): void
    {
        $rc     = new \ReflectionClass(UpdateLocationService::class);
        $source = file_get_contents($rc->getFileName());

        $this->assertStringContainsString('UpdateLocationData::fromArray', $source,
            'UpdateLocationService must build UpdateLocationData from the input array.');
    }

    public function test_update_location_service_throws_when_not_found(): void
    {
        $this->expectException(LocationNotFoundException::class);

        $repo = $this->createMock(LocationRepositoryInterface::class);
        $repo->method('find')->willReturn(null);

        $service = new UpdateLocationService($repo);

        $method = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $method->invoke($service, ['id' => 999, 'name' => 'New Name', 'type' => 'country']);
    }

    public function test_update_location_service_dispatches_updated_event(): void
    {
        $rc     = new \ReflectionClass(UpdateLocationService::class);
        $source = file_get_contents($rc->getFileName());

        $this->assertStringContainsString('LocationUpdated', $source,
            'UpdateLocationService must dispatch LocationUpdated event.');
    }

    // ── DeleteLocationServiceInterface ────────────────────────────────────────

    public function test_delete_location_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(DeleteLocationServiceInterface::class));
    }

    // ── DeleteLocationService ─────────────────────────────────────────────────

    public function test_delete_location_service_class_exists(): void
    {
        $this->assertTrue(class_exists(DeleteLocationService::class));
    }

    public function test_delete_location_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(DeleteLocationService::class, DeleteLocationServiceInterface::class),
            'DeleteLocationService must implement DeleteLocationServiceInterface.'
        );
    }

    public function test_delete_location_service_throws_when_not_found(): void
    {
        $this->expectException(LocationNotFoundException::class);

        $repo = $this->createMock(LocationRepositoryInterface::class);
        $repo->method('find')->willReturn(null);

        $service = new DeleteLocationService($repo);

        $method = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $method->invoke($service, ['id' => 999]);
    }

    public function test_delete_location_service_dispatches_deleted_event(): void
    {
        $rc     = new \ReflectionClass(DeleteLocationService::class);
        $source = file_get_contents($rc->getFileName());

        $this->assertStringContainsString('LocationDeleted', $source,
            'DeleteLocationService must dispatch LocationDeleted event.');
    }

    // ── MoveLocationServiceInterface ──────────────────────────────────────────

    public function test_move_location_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(MoveLocationServiceInterface::class));
    }

    // ── MoveLocationService ───────────────────────────────────────────────────

    public function test_move_location_service_class_exists(): void
    {
        $this->assertTrue(class_exists(MoveLocationService::class));
    }

    public function test_move_location_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(MoveLocationService::class, MoveLocationServiceInterface::class),
            'MoveLocationService must implement MoveLocationServiceInterface.'
        );
    }

    public function test_move_location_service_throws_when_not_found(): void
    {
        $this->expectException(LocationNotFoundException::class);

        $repo = $this->createMock(LocationRepositoryInterface::class);
        $repo->method('find')->willReturn(null);

        $service = new MoveLocationService($repo);

        $method = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $method->invoke($service, ['id' => 999, 'parent_id' => null]);
    }

    public function test_move_location_service_dispatches_moved_event(): void
    {
        $rc     = new \ReflectionClass(MoveLocationService::class);
        $source = file_get_contents($rc->getFileName());

        $this->assertStringContainsString('LocationMoved', $source,
            'MoveLocationService must dispatch LocationMoved event.');
    }

    // ── LocationData DTO ──────────────────────────────────────────────────────

    public function test_location_data_dto_class_exists(): void
    {
        $this->assertTrue(class_exists(LocationData::class));
    }

    public function test_location_data_dto_extends_base_dto(): void
    {
        $this->assertTrue(
            is_subclass_of(LocationData::class, \Modules\Core\Application\DTOs\BaseDto::class),
            'LocationData must extend BaseDto.'
        );
    }

    public function test_location_data_dto_has_required_properties(): void
    {
        $rc        = new \ReflectionClass(LocationData::class);
        $propNames = array_map(fn ($p) => $p->getName(), $rc->getProperties(\ReflectionProperty::IS_PUBLIC));

        $this->assertContains('tenant_id',   $propNames);
        $this->assertContains('name',        $propNames);
        $this->assertContains('type',        $propNames);
        $this->assertContains('code',        $propNames);
        $this->assertContains('description', $propNames);
        $this->assertContains('latitude',    $propNames);
        $this->assertContains('longitude',   $propNames);
        $this->assertContains('timezone',    $propNames);
        $this->assertContains('metadata',    $propNames);
        $this->assertContains('parent_id',   $propNames);
    }

    public function test_location_data_dto_from_array_populates_correctly(): void
    {
        $dto = LocationData::fromArray([
            'tenant_id'   => 1,
            'name'        => 'France',
            'type'        => 'country',
            'code'        => 'FR',
            'description' => 'Western European country',
            'latitude'    => 46.2276,
            'longitude'   => 2.2137,
            'timezone'    => 'Europe/Paris',
            'metadata'    => ['currency' => 'EUR'],
            'parent_id'   => null,
        ]);

        $this->assertSame(1, $dto->tenant_id);
        $this->assertSame('France', $dto->name);
        $this->assertSame('country', $dto->type);
        $this->assertSame('FR', $dto->code);
        $this->assertSame(46.2276, $dto->latitude);
        $this->assertSame(2.2137, $dto->longitude);
        $this->assertSame('Europe/Paris', $dto->timezone);
        $this->assertSame(['currency' => 'EUR'], $dto->metadata);
    }

    // ── UpdateLocationData DTO ────────────────────────────────────────────────

    public function test_update_location_data_dto_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdateLocationData::class));
    }

    public function test_update_location_data_dto_extends_base_dto(): void
    {
        $this->assertTrue(
            is_subclass_of(UpdateLocationData::class, \Modules\Core\Application\DTOs\BaseDto::class),
            'UpdateLocationData must extend BaseDto.'
        );
    }

    public function test_update_location_data_dto_to_array_only_returns_provided_keys(): void
    {
        $dto = UpdateLocationData::fromArray(['id' => 5, 'name' => 'New Name', 'type' => 'state']);

        $arr = $dto->toArray();

        $this->assertArrayHasKey('id',   $arr, 'id must be present because it was provided.');
        $this->assertArrayHasKey('name', $arr, 'name must be present because it was provided.');
        $this->assertArrayHasKey('type', $arr, 'type must be present because it was provided.');
        $this->assertArrayNotHasKey('code',      $arr, 'code must be absent because it was not provided.');
        $this->assertArrayNotHasKey('metadata',  $arr, 'metadata must be absent because it was not provided.');
        $this->assertArrayNotHasKey('parent_id', $arr, 'parent_id must be absent because it was not provided.');
    }

    public function test_update_location_data_dto_is_provided_returns_true_for_present_fields(): void
    {
        $dto = UpdateLocationData::fromArray(['name' => 'France', 'code' => null]);

        $this->assertTrue($dto->isProvided('name'),  'name was in the source array — isProvided must return true.');
        $this->assertTrue($dto->isProvided('code'),  'code was in the source array (as null) — isProvided must return true.');
        $this->assertFalse($dto->isProvided('description'), 'description was absent — isProvided must return false.');
        $this->assertFalse($dto->isProvided('metadata'),    'metadata was absent — isProvided must return false.');
    }

    public function test_update_location_data_dto_accepts_null_values(): void
    {
        $dto = UpdateLocationData::fromArray(['code' => null, 'latitude' => null]);

        $this->assertTrue($dto->isProvided('code'));
        $this->assertTrue($dto->isProvided('latitude'));
        $this->assertNull($dto->code);
        $this->assertNull($dto->latitude);
    }

    public function test_update_location_data_dto_does_not_have_tenant_id_property(): void
    {
        $rc        = new \ReflectionClass(UpdateLocationData::class);
        $propNames = array_map(fn ($p) => $p->getName(), $rc->getProperties(\ReflectionProperty::IS_PUBLIC));

        $this->assertNotContains('tenant_id', $propNames,
            'UpdateLocationData must not have a tenant_id property.');
    }

    // ── MoveLocationData DTO ──────────────────────────────────────────────────

    public function test_move_location_data_dto_class_exists(): void
    {
        $this->assertTrue(class_exists(MoveLocationData::class));
    }

    public function test_move_location_data_dto_has_parent_id_property(): void
    {
        $rc = new \ReflectionClass(MoveLocationData::class);
        $this->assertTrue($rc->hasProperty('parent_id'));
    }

    // ── StoreLocationRequest ──────────────────────────────────────────────────

    public function test_store_location_request_class_exists(): void
    {
        $this->assertTrue(class_exists(StoreLocationRequest::class));
    }

    public function test_store_location_request_has_required_rules(): void
    {
        $request = new StoreLocationRequest;
        $rules   = $request->rules();

        $this->assertArrayHasKey('tenant_id',   $rules);
        $this->assertArrayHasKey('name',        $rules);
        $this->assertArrayHasKey('type',        $rules);
        $this->assertArrayHasKey('code',        $rules);
        $this->assertArrayHasKey('description', $rules);
        $this->assertArrayHasKey('latitude',    $rules);
        $this->assertArrayHasKey('longitude',   $rules);
        $this->assertArrayHasKey('timezone',    $rules);
        $this->assertArrayHasKey('metadata',    $rules);
        $this->assertArrayHasKey('parent_id',   $rules);
    }

    public function test_store_location_request_tenant_id_is_required(): void
    {
        $request = new StoreLocationRequest;
        $rules   = $request->rules();

        $this->assertStringContainsString('required', $rules['tenant_id']);
    }

    public function test_store_location_request_name_is_required(): void
    {
        $request = new StoreLocationRequest;
        $rules   = $request->rules();

        $this->assertStringContainsString('required', $rules['name']);
    }

    public function test_store_location_request_type_is_required(): void
    {
        $request = new StoreLocationRequest;
        $rules   = $request->rules();

        $this->assertStringContainsString('required', $rules['type']);
    }

    public function test_store_location_request_code_is_nullable(): void
    {
        $request = new StoreLocationRequest;
        $rules   = $request->rules();

        $this->assertStringContainsString('nullable', $rules['code']);
    }

    public function test_store_location_request_latitude_is_nullable(): void
    {
        $request = new StoreLocationRequest;
        $rules   = $request->rules();

        $this->assertStringContainsString('nullable', $rules['latitude']);
    }

    // ── UpdateLocationRequest ─────────────────────────────────────────────────

    public function test_update_location_request_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdateLocationRequest::class));
    }

    public function test_update_location_request_name_uses_sometimes_required(): void
    {
        $request = new UpdateLocationRequest;
        $rules   = $request->rules();

        $this->assertStringContainsString('sometimes', $rules['name']);
        $this->assertStringContainsString('required', $rules['name']);
    }

    public function test_update_location_request_type_uses_sometimes_required(): void
    {
        $request = new UpdateLocationRequest;
        $rules   = $request->rules();

        $this->assertStringContainsString('sometimes', $rules['type']);
        $this->assertStringContainsString('required', $rules['type']);
    }

    // ── MoveLocationRequest ───────────────────────────────────────────────────

    public function test_move_location_request_class_exists(): void
    {
        $this->assertTrue(class_exists(MoveLocationRequest::class));
    }

    public function test_move_location_request_has_parent_id_rule(): void
    {
        $request = new MoveLocationRequest;
        $rules   = $request->rules();

        $this->assertArrayHasKey('parent_id', $rules);
        $this->assertStringContainsString('nullable', $rules['parent_id']);
    }

    // ── LocationResource ──────────────────────────────────────────────────────

    public function test_location_resource_class_exists(): void
    {
        $this->assertTrue(class_exists(LocationResource::class));
    }

    public function test_location_resource_to_array_contains_all_fields(): void
    {
        $location = new Location(
            tenantId:  1,
            name:      new Name('France'),
            type:      'country',
            code:      new Code('FR'),
            latitude:  46.2276,
            longitude: 2.2137,
            timezone:  'Europe/Paris'
        );

        $ref = new \ReflectionProperty($location, 'id');
        $ref->setAccessible(true);
        $ref->setValue($location, 1);

        $resource = new LocationResource($location);
        $array    = $resource->toArray(null);

        $this->assertArrayHasKey('id',          $array);
        $this->assertArrayHasKey('tenant_id',   $array);
        $this->assertArrayHasKey('name',        $array);
        $this->assertArrayHasKey('type',        $array);
        $this->assertArrayHasKey('code',        $array);
        $this->assertArrayHasKey('description', $array);
        $this->assertArrayHasKey('latitude',    $array);
        $this->assertArrayHasKey('longitude',   $array);
        $this->assertArrayHasKey('timezone',    $array);
        $this->assertArrayHasKey('metadata',    $array);
        $this->assertArrayHasKey('parent_id',   $array);
        $this->assertArrayHasKey('children',    $array);
        $this->assertArrayHasKey('created_at',  $array);
        $this->assertArrayHasKey('updated_at',  $array);

        $this->assertSame('France', $array['name']);
        $this->assertSame('country', $array['type']);
        $this->assertSame('FR', $array['code']);
        $this->assertSame(46.2276, $array['latitude']);
        $this->assertSame(2.2137, $array['longitude']);
        $this->assertSame('Europe/Paris', $array['timezone']);
    }

    // ── LocationCollection ────────────────────────────────────────────────────

    public function test_location_collection_class_exists(): void
    {
        $this->assertTrue(class_exists(LocationCollection::class));
    }

    public function test_location_collection_collects_location_resource(): void
    {
        $rc = new \ReflectionClass(LocationCollection::class);

        $this->assertTrue($rc->hasProperty('collects'));
        $prop = $rc->getProperty('collects');
        $prop->setAccessible(true);
        $instance = new LocationCollection(collect());
        $this->assertSame(LocationResource::class, $prop->getValue($instance));
    }

    // ── LocationTreeResource ──────────────────────────────────────────────────

    public function test_location_tree_resource_class_exists(): void
    {
        $this->assertTrue(class_exists(LocationTreeResource::class));
    }

    public function test_location_tree_resource_to_array_maps_collection(): void
    {
        $child  = $this->createTestLocation(2, 1, 'state');
        $parent = $this->createTestLocation(1, 1, 'country');
        $parent->addChild($child);

        $resource = new LocationTreeResource(collect([$parent]));
        $array    = $resource->toArray(null);

        $this->assertCount(1, $array);
        $this->assertArrayHasKey('id',       $array[0]);
        $this->assertArrayHasKey('name',     $array[0]);
        $this->assertArrayHasKey('type',     $array[0]);
        $this->assertArrayHasKey('code',     $array[0]);
        $this->assertArrayHasKey('children', $array[0]);
    }

    // ── LocationController ────────────────────────────────────────────────────

    public function test_location_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(LocationController::class));
    }

    public function test_location_controller_extends_authorized_controller(): void
    {
        $this->assertTrue(
            is_subclass_of(LocationController::class, \Modules\Core\Infrastructure\Http\Controllers\AuthorizedController::class),
            'LocationController must extend AuthorizedController.'
        );
    }

    public function test_location_controller_injects_find_service(): void
    {
        $rc = new \ReflectionClass(LocationController::class);

        $paramTypes = [];
        foreach ($rc->getConstructor()->getParameters() as $param) {
            $paramTypes[] = $param->getType()?->getName();
        }

        $this->assertContains(FindLocationServiceInterface::class, $paramTypes,
            'LocationController must inject FindLocationServiceInterface.');
    }

    public function test_location_controller_injects_create_service(): void
    {
        $rc = new \ReflectionClass(LocationController::class);

        $paramTypes = [];
        foreach ($rc->getConstructor()->getParameters() as $param) {
            $paramTypes[] = $param->getType()?->getName();
        }

        $this->assertContains(CreateLocationServiceInterface::class, $paramTypes,
            'LocationController must inject CreateLocationServiceInterface.');
    }

    public function test_location_controller_injects_update_service(): void
    {
        $rc = new \ReflectionClass(LocationController::class);

        $paramTypes = [];
        foreach ($rc->getConstructor()->getParameters() as $param) {
            $paramTypes[] = $param->getType()?->getName();
        }

        $this->assertContains(UpdateLocationServiceInterface::class, $paramTypes,
            'LocationController must inject UpdateLocationServiceInterface.');
    }

    public function test_location_controller_injects_delete_service(): void
    {
        $rc = new \ReflectionClass(LocationController::class);

        $paramTypes = [];
        foreach ($rc->getConstructor()->getParameters() as $param) {
            $paramTypes[] = $param->getType()?->getName();
        }

        $this->assertContains(DeleteLocationServiceInterface::class, $paramTypes,
            'LocationController must inject DeleteLocationServiceInterface.');
    }

    public function test_location_controller_injects_move_service(): void
    {
        $rc = new \ReflectionClass(LocationController::class);

        $paramTypes = [];
        foreach ($rc->getConstructor()->getParameters() as $param) {
            $paramTypes[] = $param->getType()?->getName();
        }

        $this->assertContains(MoveLocationServiceInterface::class, $paramTypes,
            'LocationController must inject MoveLocationServiceInterface.');
    }

    public function test_location_controller_has_index_method(): void
    {
        $this->assertTrue(
            method_exists(LocationController::class, 'index'),
            'LocationController must have an index() method.'
        );
    }

    public function test_location_controller_has_store_method(): void
    {
        $this->assertTrue(
            method_exists(LocationController::class, 'store'),
            'LocationController must have a store() method.'
        );
    }

    public function test_location_controller_has_show_method(): void
    {
        $this->assertTrue(
            method_exists(LocationController::class, 'show'),
            'LocationController must have a show() method.'
        );
    }

    public function test_location_controller_has_update_method(): void
    {
        $this->assertTrue(
            method_exists(LocationController::class, 'update'),
            'LocationController must have an update() method.'
        );
    }

    public function test_location_controller_has_destroy_method(): void
    {
        $this->assertTrue(
            method_exists(LocationController::class, 'destroy'),
            'LocationController must have a destroy() method.'
        );
    }

    public function test_location_controller_has_tree_method(): void
    {
        $this->assertTrue(
            method_exists(LocationController::class, 'tree'),
            'LocationController must have a tree() method.'
        );
    }

    public function test_location_controller_has_move_method(): void
    {
        $this->assertTrue(
            method_exists(LocationController::class, 'move'),
            'LocationController must have a move() method.'
        );
    }

    public function test_location_controller_has_descendants_method(): void
    {
        $this->assertTrue(
            method_exists(LocationController::class, 'descendants'),
            'LocationController must have a descendants() method.'
        );
    }

    public function test_location_controller_has_ancestors_method(): void
    {
        $this->assertTrue(
            method_exists(LocationController::class, 'ancestors'),
            'LocationController must have an ancestors() method.'
        );
    }

    public function test_location_controller_does_not_inject_repository_directly(): void
    {
        $rc = new \ReflectionClass(LocationController::class);

        foreach ($rc->getConstructor()->getParameters() as $param) {
            $type = $param->getType()?->getName();
            $this->assertNotSame(LocationRepositoryInterface::class, $type,
                'LocationController must not directly depend on LocationRepositoryInterface (DIP violation).');
        }
    }

    // ── EloquentLocationRepository ────────────────────────────────────────────

    public function test_eloquent_location_repository_class_exists(): void
    {
        $this->assertTrue(class_exists(EloquentLocationRepository::class));
    }

    public function test_eloquent_location_repository_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(EloquentLocationRepository::class, LocationRepositoryInterface::class),
            'EloquentLocationRepository must implement LocationRepositoryInterface.'
        );
    }

    public function test_eloquent_location_repository_has_required_methods(): void
    {
        $this->assertTrue(method_exists(EloquentLocationRepository::class, 'save'));
        $this->assertTrue(method_exists(EloquentLocationRepository::class, 'getTree'));
        $this->assertTrue(method_exists(EloquentLocationRepository::class, 'getDescendants'));
        $this->assertTrue(method_exists(EloquentLocationRepository::class, 'getAncestors'));
        $this->assertTrue(method_exists(EloquentLocationRepository::class, 'moveNode'));
        $this->assertTrue(method_exists(EloquentLocationRepository::class, 'rebuildTree'));
    }

    // ── LocationModel ─────────────────────────────────────────────────────────

    public function test_location_model_class_exists(): void
    {
        $this->assertTrue(class_exists(LocationModel::class));
    }

    public function test_location_model_has_get_descendants_method(): void
    {
        $this->assertTrue(
            method_exists(LocationModel::class, 'getDescendants'),
            'LocationModel must declare a getDescendants() helper for nested set queries.'
        );
    }

    public function test_location_model_has_get_ancestors_method(): void
    {
        $this->assertTrue(
            method_exists(LocationModel::class, 'getAncestors'),
            'LocationModel must declare a getAncestors() helper for nested set queries.'
        );
    }

    public function test_location_model_uses_soft_deletes(): void
    {
        $traits = class_uses_recursive(LocationModel::class);

        $this->assertContains(
            \Illuminate\Database\Eloquent\SoftDeletes::class,
            $traits,
            'LocationModel must use SoftDeletes.'
        );
    }

    public function test_location_model_has_required_fillable_fields(): void
    {
        $model    = new LocationModel;
        $fillable = $model->getFillable();

        $this->assertContains('tenant_id',   $fillable);
        $this->assertContains('name',        $fillable);
        $this->assertContains('type',        $fillable);
        $this->assertContains('code',        $fillable);
        $this->assertContains('description', $fillable);
        $this->assertContains('latitude',    $fillable);
        $this->assertContains('longitude',   $fillable);
        $this->assertContains('timezone',    $fillable);
        $this->assertContains('metadata',    $fillable);
        $this->assertContains('parent_id',   $fillable);
        $this->assertContains('_lft',        $fillable);
        $this->assertContains('_rgt',        $fillable);
    }

    // ── LocationServiceProvider ───────────────────────────────────────────────

    public function test_location_service_provider_class_exists(): void
    {
        $this->assertTrue(class_exists(LocationServiceProvider::class));
    }

    public function test_location_service_provider_registers_find_service(): void
    {
        $rc       = new \ReflectionClass(LocationServiceProvider::class);
        $method   = $rc->getMethod('register');
        $filename = $rc->getFileName();
        $start    = $method->getStartLine();
        $end      = $method->getEndLine();
        $lines    = array_slice(file($filename), $start - 1, $end - $start + 1);
        $body     = implode('', $lines);

        $this->assertStringContainsString('FindLocationServiceInterface', $body,
            'ServiceProvider must bind FindLocationServiceInterface.');
    }

    public function test_location_service_provider_registers_create_service(): void
    {
        $rc       = new \ReflectionClass(LocationServiceProvider::class);
        $method   = $rc->getMethod('register');
        $filename = $rc->getFileName();
        $start    = $method->getStartLine();
        $end      = $method->getEndLine();
        $lines    = array_slice(file($filename), $start - 1, $end - $start + 1);
        $body     = implode('', $lines);

        $this->assertStringContainsString('CreateLocationServiceInterface', $body,
            'ServiceProvider must bind CreateLocationServiceInterface.');
    }

    public function test_location_service_provider_registers_update_service(): void
    {
        $rc       = new \ReflectionClass(LocationServiceProvider::class);
        $method   = $rc->getMethod('register');
        $filename = $rc->getFileName();
        $start    = $method->getStartLine();
        $end      = $method->getEndLine();
        $lines    = array_slice(file($filename), $start - 1, $end - $start + 1);
        $body     = implode('', $lines);

        $this->assertStringContainsString('UpdateLocationServiceInterface', $body,
            'ServiceProvider must bind UpdateLocationServiceInterface.');
    }

    public function test_location_service_provider_registers_delete_service(): void
    {
        $rc       = new \ReflectionClass(LocationServiceProvider::class);
        $method   = $rc->getMethod('register');
        $filename = $rc->getFileName();
        $start    = $method->getStartLine();
        $end      = $method->getEndLine();
        $lines    = array_slice(file($filename), $start - 1, $end - $start + 1);
        $body     = implode('', $lines);

        $this->assertStringContainsString('DeleteLocationServiceInterface', $body,
            'ServiceProvider must bind DeleteLocationServiceInterface.');
    }

    public function test_location_service_provider_registers_move_service(): void
    {
        $rc       = new \ReflectionClass(LocationServiceProvider::class);
        $method   = $rc->getMethod('register');
        $filename = $rc->getFileName();
        $start    = $method->getStartLine();
        $end      = $method->getEndLine();
        $lines    = array_slice(file($filename), $start - 1, $end - $start + 1);
        $body     = implode('', $lines);

        $this->assertStringContainsString('MoveLocationServiceInterface', $body,
            'ServiceProvider must bind MoveLocationServiceInterface.');
    }

    public function test_location_service_provider_registers_repository(): void
    {
        $rc       = new \ReflectionClass(LocationServiceProvider::class);
        $method   = $rc->getMethod('register');
        $filename = $rc->getFileName();
        $start    = $method->getStartLine();
        $end      = $method->getEndLine();
        $lines    = array_slice(file($filename), $start - 1, $end - $start + 1);
        $body     = implode('', $lines);

        $this->assertStringContainsString('LocationRepositoryInterface', $body,
            'ServiceProvider must bind LocationRepositoryInterface.');
    }

    // ── Routes ────────────────────────────────────────────────────────────────

    public function test_routes_file_exists(): void
    {
        $routesFile = __DIR__.'/../../app/Modules/Location/routes/api.php';
        $this->assertFileExists($routesFile);
    }

    public function test_routes_file_has_apiresource_route(): void
    {
        $routesFile = __DIR__.'/../../app/Modules/Location/routes/api.php';
        $content    = file_get_contents($routesFile);

        $this->assertStringContainsString('apiResource', $content,
            'Routes must declare a RESTful apiResource for locations.');
    }

    public function test_routes_file_has_tree_route(): void
    {
        $routesFile = __DIR__.'/../../app/Modules/Location/routes/api.php';
        $content    = file_get_contents($routesFile);

        $this->assertStringContainsString('tree', $content,
            'Routes must include a tree endpoint.');
    }

    public function test_routes_file_has_descendants_route(): void
    {
        $routesFile = __DIR__.'/../../app/Modules/Location/routes/api.php';
        $content    = file_get_contents($routesFile);

        $this->assertStringContainsString('descendants', $content,
            'Routes must include a descendants endpoint.');
    }

    public function test_routes_file_has_ancestors_route(): void
    {
        $routesFile = __DIR__.'/../../app/Modules/Location/routes/api.php';
        $content    = file_get_contents($routesFile);

        $this->assertStringContainsString('ancestors', $content,
            'Routes must include an ancestors endpoint.');
    }

    public function test_routes_file_has_move_route(): void
    {
        $routesFile = __DIR__.'/../../app/Modules/Location/routes/api.php';
        $content    = file_get_contents($routesFile);

        $this->assertStringContainsString('move', $content,
            'Routes must include a move endpoint.');
    }

    public function test_routes_file_tree_route_is_declared_before_resource(): void
    {
        $routesFile = __DIR__.'/../../app/Modules/Location/routes/api.php';
        $content    = file_get_contents($routesFile);

        $treePos     = strpos($content, 'locations/tree');
        $resourcePos = strpos($content, 'apiResource');

        $this->assertNotFalse($treePos, 'Routes file must contain tree route.');
        $this->assertNotFalse($resourcePos, 'Routes file must contain apiResource.');
        $this->assertLessThan($resourcePos, $treePos,
            'Tree route must be declared before apiResource to prevent wildcard collision.');
    }

    // ── Migration ─────────────────────────────────────────────────────────────

    public function test_migration_file_exists(): void
    {
        $migrationDir = __DIR__.'/../../app/Modules/Location/database/migrations/';
        $files        = glob($migrationDir.'*_create_locations_table.php');

        $this->assertNotEmpty($files, 'A migration file for creating the locations table must exist.');
    }

    public function test_migration_creates_locations_table(): void
    {
        $migrationDir = __DIR__.'/../../app/Modules/Location/database/migrations/';
        $files        = glob($migrationDir.'*_create_locations_table.php');

        $this->assertNotEmpty($files);
        $content = file_get_contents($files[0]);

        $this->assertStringContainsString("'locations'", $content);
    }

    public function test_migration_has_nested_set_columns(): void
    {
        $migrationDir = __DIR__.'/../../app/Modules/Location/database/migrations/';
        $files        = glob($migrationDir.'*_create_locations_table.php');

        $this->assertNotEmpty($files);
        $content = file_get_contents($files[0]);

        $this->assertStringContainsString('_lft', $content, 'Migration must include _lft column for nested set.');
        $this->assertStringContainsString('_rgt', $content, 'Migration must include _rgt column for nested set.');
    }

    public function test_migration_has_type_column(): void
    {
        $migrationDir = __DIR__.'/../../app/Modules/Location/database/migrations/';
        $files        = glob($migrationDir.'*_create_locations_table.php');

        $this->assertNotEmpty($files);
        $content = file_get_contents($files[0]);

        $this->assertStringContainsString('type', $content, 'Migration must include type column.');
    }

    public function test_migration_has_geo_columns(): void
    {
        $migrationDir = __DIR__.'/../../app/Modules/Location/database/migrations/';
        $files        = glob($migrationDir.'*_create_locations_table.php');

        $this->assertNotEmpty($files);
        $content = file_get_contents($files[0]);

        $this->assertStringContainsString('latitude', $content, 'Migration must include latitude column.');
        $this->assertStringContainsString('longitude', $content, 'Migration must include longitude column.');
    }

    public function test_migration_has_soft_deletes(): void
    {
        $migrationDir = __DIR__.'/../../app/Modules/Location/database/migrations/';
        $files        = glob($migrationDir.'*_create_locations_table.php');

        $this->assertNotEmpty($files);
        $content = file_get_contents($files[0]);

        $this->assertStringContainsString('softDeletes', $content, 'Migration must include softDeletes.');
    }

    // ── Bootstrap Registration ────────────────────────────────────────────────

    public function test_location_service_provider_is_registered_in_bootstrap(): void
    {
        $bootstrapFile = __DIR__.'/../../bootstrap/providers.php';
        $content       = file_get_contents($bootstrapFile);

        $this->assertStringContainsString('LocationServiceProvider', $content,
            'LocationServiceProvider must be registered in bootstrap/providers.php.');
    }
}
