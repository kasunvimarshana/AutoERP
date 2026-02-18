<?php

declare(strict_types=1);

namespace Modules\Organization\Tests\Unit;

use Mockery;
use Tests\TestCase;
use Modules\Organization\Entities\Organization;
use Modules\Organization\Entities\Location;
use Modules\Organization\Entities\OrganizationalUnit;
use Modules\Organization\Repositories\Contracts\OrganizationalUnitRepositoryInterface;
use Modules\Organization\Repositories\Contracts\OrganizationRepositoryInterface;
use Modules\Organization\Repositories\Contracts\LocationRepositoryInterface;
use Modules\Organization\Services\OrganizationalUnitService;

class OrganizationalUnitServiceTest extends TestCase
{
    protected OrganizationalUnitService $service;
    protected $unitRepository;
    protected $organizationRepository;
    protected $locationRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unitRepository = Mockery::mock(OrganizationalUnitRepositoryInterface::class);
        $this->organizationRepository = Mockery::mock(OrganizationRepositoryInterface::class);
        $this->locationRepository = Mockery::mock(LocationRepositoryInterface::class);

        $this->service = new OrganizationalUnitService(
            $this->unitRepository,
            $this->organizationRepository,
            $this->locationRepository
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_creates_organizational_unit_successfully(): void
    {
        // Arrange
        $data = [
            'organization_id' => 1,
            'code' => 'UNIT-001',
            'name' => ['en' => 'Engineering Department'],
            'unit_type' => 'department',
            'status' => 'active',
        ];

        $organization = new Organization(['id' => 1]);
        $unit = new OrganizationalUnit($data);
        $unit->id = 1;

        $this->organizationRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($organization);

        $this->unitRepository
            ->shouldReceive('findByCode')
            ->once()
            ->with('UNIT-001')
            ->andReturn(null);

        $this->unitRepository
            ->shouldReceive('create')
            ->once()
            ->with($data)
            ->andReturn($unit);

        // Act
        $result = $this->service->createUnit($data);

        // Assert
        $this->assertInstanceOf(OrganizationalUnit::class, $result);
        $this->assertEquals(1, $result->id);
    }

    public function test_it_throws_exception_when_organization_not_found(): void
    {
        // Arrange
        $data = [
            'organization_id' => 999,
            'code' => 'UNIT-001',
        ];

        $this->organizationRepository
            ->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Organization not found');

        // Act
        $this->service->createUnit($data);
    }

    public function test_it_throws_exception_when_code_already_exists(): void
    {
        // Arrange
        $data = [
            'organization_id' => 1,
            'code' => 'UNIT-001',
        ];

        $organization = new Organization(['id' => 1]);
        $existingUnit = new OrganizationalUnit(['id' => 2, 'code' => 'UNIT-001']);

        $this->organizationRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($organization);

        $this->unitRepository
            ->shouldReceive('findByCode')
            ->once()
            ->with('UNIT-001')
            ->andReturn($existingUnit);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Organizational unit code already exists');

        // Act
        $this->service->createUnit($data);
    }

    public function test_it_validates_location_belongs_to_organization(): void
    {
        // Arrange
        $data = [
            'organization_id' => 1,
            'location_id' => 10,
            'code' => 'UNIT-001',
        ];

        $organization = new Organization(['id' => 1]);
        $location = new Location(['id' => 10, 'organization_id' => 2]); // Different org

        $this->organizationRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($organization);

        $this->locationRepository
            ->shouldReceive('findById')
            ->once()
            ->with(10)
            ->andReturn($location);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Location does not belong to the specified organization');

        // Act
        $this->service->createUnit($data);
    }

    public function test_it_updates_organizational_unit_successfully(): void
    {
        // Arrange
        $unitId = 1;
        $data = [
            'name' => ['en' => 'Updated Department'],
            'status' => 'inactive',
        ];

        $unit = new OrganizationalUnit([
            'id' => $unitId,
            'code' => 'UNIT-001',
            'organization_id' => 1,
        ]);

        $updatedUnit = new OrganizationalUnit(array_merge($unit->toArray(), $data));

        $this->unitRepository
            ->shouldReceive('findById')
            ->once()
            ->with($unitId)
            ->andReturn($unit);

        $this->unitRepository
            ->shouldReceive('update')
            ->once()
            ->with($unit, $data)
            ->andReturn($updatedUnit);

        // Act
        $result = $this->service->updateUnit($unitId, $data);

        // Assert
        $this->assertInstanceOf(OrganizationalUnit::class, $result);
    }

    public function test_it_prevents_self_parent_reference(): void
    {
        // Arrange
        $unitId = 1;
        $data = [
            'parent_unit_id' => 1, // Same as unit ID
        ];

        $unit = new OrganizationalUnit([
            'id' => $unitId,
            'organization_id' => 1,
        ]);

        $this->unitRepository
            ->shouldReceive('findById')
            ->once()
            ->with($unitId)
            ->andReturn($unit);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Organizational unit cannot be its own parent');

        // Act
        $this->service->updateUnit($unitId, $data);
    }

    public function test_it_deletes_organizational_unit_without_children(): void
    {
        // Arrange
        $unitId = 1;
        $unit = Mockery::mock(OrganizationalUnit::class);
        $unit->shouldReceive('children->count')->once()->andReturn(0);

        $this->unitRepository
            ->shouldReceive('findById')
            ->once()
            ->with($unitId)
            ->andReturn($unit);

        $this->unitRepository
            ->shouldReceive('delete')
            ->once()
            ->with($unit)
            ->andReturn(true);

        // Act
        $result = $this->service->deleteUnit($unitId);

        // Assert
        $this->assertTrue($result);
    }

    public function test_it_prevents_deletion_of_unit_with_children(): void
    {
        // Arrange
        $unitId = 1;
        $unit = Mockery::mock(OrganizationalUnit::class);
        $unit->shouldReceive('children->count')->once()->andReturn(2);

        $this->unitRepository
            ->shouldReceive('findById')
            ->once()
            ->with($unitId)
            ->andReturn($unit);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot delete organizational unit with children');

        // Act
        $this->service->deleteUnit($unitId);
    }
}
