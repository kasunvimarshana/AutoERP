<?php

declare(strict_types=1);

namespace Modules\Organisation\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Organisation\Application\DTOs\CreateBranchDTO;
use Modules\Organisation\Application\DTOs\CreateDepartmentDTO;
use Modules\Organisation\Application\DTOs\CreateLocationDTO;
use Modules\Organisation\Application\DTOs\CreateOrganisationDTO;
use Modules\Organisation\Application\Services\OrganisationService;
use Modules\Organisation\Domain\Contracts\BranchRepositoryContract;
use Modules\Organisation\Domain\Contracts\DepartmentRepositoryContract;
use Modules\Organisation\Domain\Contracts\LocationRepositoryContract;
use Modules\Organisation\Domain\Contracts\OrganisationRepositoryContract;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for OrganisationService.
 *
 * The repository is mocked — no database or Laravel bootstrap required.
 * These tests exercise the delegation and argument-passing logic in the service.
 *
 * Write methods (create, update, delete and hierarchy variants) use DB::transaction()
 * which requires the Laravel facade; those paths are covered by feature tests.
 * The unit tests verify the DTO-to-array mapping and delegation logic.
 */
class OrganisationServiceTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeService(
        ?OrganisationRepositoryContract $orgRepo = null,
        ?BranchRepositoryContract $branchRepo = null,
        ?LocationRepositoryContract $locationRepo = null,
        ?DepartmentRepositoryContract $deptRepo = null,
    ): OrganisationService {
        return new OrganisationService(
            $orgRepo       ?? $this->createMock(OrganisationRepositoryContract::class),
            $branchRepo    ?? $this->createMock(BranchRepositoryContract::class),
            $locationRepo  ?? $this->createMock(LocationRepositoryContract::class),
            $deptRepo      ?? $this->createMock(DepartmentRepositoryContract::class),
        );
    }

    // -------------------------------------------------------------------------
    // list — paginate delegation
    // -------------------------------------------------------------------------

    public function test_list_delegates_to_repository_paginate_with_default_per_page(): void
    {
        $paginator = $this->createMock(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);

        $repo = $this->createMock(OrganisationRepositoryContract::class);
        $repo->expects($this->once())
            ->method('paginate')
            ->with(15)
            ->willReturn($paginator);

        $service = $this->makeService($repo);
        $result  = $service->list();

        $this->assertSame($paginator, $result);
    }

    public function test_list_passes_custom_per_page_to_paginate(): void
    {
        $paginator = $this->createMock(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);

        $repo = $this->createMock(OrganisationRepositoryContract::class);
        $repo->expects($this->once())
            ->method('paginate')
            ->with(30)
            ->willReturn($paginator);

        $service = $this->makeService($repo);
        $service->list(30);
    }

    // -------------------------------------------------------------------------
    // show — delegates to findOrFail
    // -------------------------------------------------------------------------

    public function test_show_delegates_to_find_or_fail(): void
    {
        $model = $this->createMock(Model::class);

        $repo = $this->createMock(OrganisationRepositoryContract::class);
        $repo->expects($this->once())
            ->method('findOrFail')
            ->with(10)
            ->willReturn($model);

        $service = $this->makeService($repo);
        $result  = $service->show(10);

        $this->assertSame($model, $result);
    }

    public function test_show_accepts_string_id(): void
    {
        $model = $this->createMock(Model::class);

        $repo = $this->createMock(OrganisationRepositoryContract::class);
        $repo->expects($this->once())
            ->method('findOrFail')
            ->with('uuid-1234')
            ->willReturn($model);

        $service = $this->makeService($repo);
        $result  = $service->show('uuid-1234');

        $this->assertSame($model, $result);
    }

    // -------------------------------------------------------------------------
    // CreateOrganisationDTO — hydration validation
    // -------------------------------------------------------------------------

    public function test_dto_hydrates_all_fields(): void
    {
        $dto = CreateOrganisationDTO::fromArray([
            'name'        => 'Acme Corp',
            'code'        => 'ACME',
            'description' => 'Main holding company',
            'is_active'   => true,
        ]);

        $this->assertSame('Acme Corp', $dto->name);
        $this->assertSame('ACME', $dto->code);
        $this->assertSame('Main holding company', $dto->description);
        $this->assertTrue($dto->isActive);
    }

    public function test_dto_defaults_description_to_null(): void
    {
        $dto = CreateOrganisationDTO::fromArray([
            'name' => 'Beta Ltd',
            'code' => 'BETA',
        ]);

        $this->assertNull($dto->description);
        $this->assertTrue($dto->isActive);
    }

    public function test_dto_can_set_is_active_false(): void
    {
        $dto = CreateOrganisationDTO::fromArray([
            'name'      => 'Inactive Org',
            'code'      => 'INACT',
            'is_active' => false,
        ]);

        $this->assertFalse($dto->isActive);
    }

    // -------------------------------------------------------------------------
    // CreateBranchDTO — hydration validation
    // -------------------------------------------------------------------------

    public function test_branch_dto_hydrates_all_fields(): void
    {
        $dto = CreateBranchDTO::fromArray([
            'organisation_id' => 1,
            'name'            => 'HQ Branch',
            'code'            => 'HQ',
            'address'         => '123 Main St',
            'is_active'       => true,
        ]);

        $this->assertSame(1, $dto->organisationId);
        $this->assertSame('HQ Branch', $dto->name);
        $this->assertSame('HQ', $dto->code);
        $this->assertSame('123 Main St', $dto->address);
        $this->assertTrue($dto->isActive);
    }

    public function test_branch_dto_defaults_address_to_null(): void
    {
        $dto = CreateBranchDTO::fromArray([
            'organisation_id' => 2,
            'name'            => 'Remote Branch',
            'code'            => 'REM',
        ]);

        $this->assertNull($dto->address);
        $this->assertTrue($dto->isActive);
    }

    public function test_branch_dto_can_set_is_active_false(): void
    {
        $dto = CreateBranchDTO::fromArray([
            'organisation_id' => 3,
            'name'            => 'Closed Branch',
            'code'            => 'CLO',
            'is_active'       => false,
        ]);

        $this->assertFalse($dto->isActive);
    }

    // -------------------------------------------------------------------------
    // CreateLocationDTO — hydration validation
    // -------------------------------------------------------------------------

    public function test_location_dto_hydrates_all_fields(): void
    {
        $dto = CreateLocationDTO::fromArray([
            'branch_id'   => 5,
            'name'        => 'Warehouse A',
            'code'        => 'WH-A',
            'description' => 'Primary warehouse',
            'is_active'   => true,
        ]);

        $this->assertSame(5, $dto->branchId);
        $this->assertSame('Warehouse A', $dto->name);
        $this->assertSame('WH-A', $dto->code);
        $this->assertSame('Primary warehouse', $dto->description);
        $this->assertTrue($dto->isActive);
    }

    public function test_location_dto_defaults_description_to_null(): void
    {
        $dto = CreateLocationDTO::fromArray([
            'branch_id' => 5,
            'name'      => 'Store Front',
            'code'      => 'SF',
        ]);

        $this->assertNull($dto->description);
        $this->assertTrue($dto->isActive);
    }

    // -------------------------------------------------------------------------
    // CreateDepartmentDTO — hydration validation
    // -------------------------------------------------------------------------

    public function test_department_dto_hydrates_all_fields(): void
    {
        $dto = CreateDepartmentDTO::fromArray([
            'location_id' => 10,
            'name'        => 'Finance',
            'code'        => 'FIN',
            'is_active'   => true,
        ]);

        $this->assertSame(10, $dto->locationId);
        $this->assertSame('Finance', $dto->name);
        $this->assertSame('FIN', $dto->code);
        $this->assertTrue($dto->isActive);
    }

    public function test_department_dto_can_set_is_active_false(): void
    {
        $dto = CreateDepartmentDTO::fromArray([
            'location_id' => 10,
            'name'        => 'Archived Dept',
            'code'        => 'ARC',
            'is_active'   => false,
        ]);

        $this->assertFalse($dto->isActive);
    }

    // -------------------------------------------------------------------------
    // listBranches — delegates to repository
    // -------------------------------------------------------------------------

    public function test_list_branches_delegates_to_repository(): void
    {
        $collection = new Collection();

        $branchRepo = $this->createMock(BranchRepositoryContract::class);
        $branchRepo->expects($this->once())
            ->method('findByOrganisation')
            ->with(1)
            ->willReturn($collection);

        $service = $this->makeService(branchRepo: $branchRepo);
        $result  = $service->listBranches(1);

        $this->assertSame($collection, $result);
    }

    public function test_list_branches_returns_collection_type(): void
    {
        $branchRepo = $this->createMock(BranchRepositoryContract::class);
        $branchRepo->method('findByOrganisation')->willReturn(new Collection());

        $service = $this->makeService(branchRepo: $branchRepo);
        $result  = $service->listBranches(99);

        $this->assertInstanceOf(Collection::class, $result);
    }

    // -------------------------------------------------------------------------
    // showBranch — delegates to findOrFail
    // -------------------------------------------------------------------------

    public function test_show_branch_delegates_to_find_or_fail(): void
    {
        $model = $this->createMock(Model::class);

        $branchRepo = $this->createMock(BranchRepositoryContract::class);
        $branchRepo->expects($this->once())
            ->method('findOrFail')
            ->with(42)
            ->willReturn($model);

        $service = $this->makeService(branchRepo: $branchRepo);
        $result  = $service->showBranch(42);

        $this->assertSame($model, $result);
    }

    // -------------------------------------------------------------------------
    // listLocations — delegates to repository
    // -------------------------------------------------------------------------

    public function test_list_locations_delegates_to_repository(): void
    {
        $collection = new Collection();

        $locationRepo = $this->createMock(LocationRepositoryContract::class);
        $locationRepo->expects($this->once())
            ->method('findByBranch')
            ->with(3)
            ->willReturn($collection);

        $service = $this->makeService(locationRepo: $locationRepo);
        $result  = $service->listLocations(3);

        $this->assertSame($collection, $result);
    }

    // -------------------------------------------------------------------------
    // showLocation — delegates to findOrFail
    // -------------------------------------------------------------------------

    public function test_show_location_delegates_to_find_or_fail(): void
    {
        $model = $this->createMock(Model::class);

        $locationRepo = $this->createMock(LocationRepositoryContract::class);
        $locationRepo->expects($this->once())
            ->method('findOrFail')
            ->with(7)
            ->willReturn($model);

        $service = $this->makeService(locationRepo: $locationRepo);
        $result  = $service->showLocation(7);

        $this->assertSame($model, $result);
    }

    // -------------------------------------------------------------------------
    // listDepartments — delegates to repository
    // -------------------------------------------------------------------------

    public function test_list_departments_delegates_to_repository(): void
    {
        $collection = new Collection();

        $deptRepo = $this->createMock(DepartmentRepositoryContract::class);
        $deptRepo->expects($this->once())
            ->method('findByLocation')
            ->with(5)
            ->willReturn($collection);

        $service = $this->makeService(deptRepo: $deptRepo);
        $result  = $service->listDepartments(5);

        $this->assertSame($collection, $result);
    }

    // -------------------------------------------------------------------------
    // showDepartment — delegates to findOrFail
    // -------------------------------------------------------------------------

    public function test_show_department_delegates_to_find_or_fail(): void
    {
        $model = $this->createMock(Model::class);

        $deptRepo = $this->createMock(DepartmentRepositoryContract::class);
        $deptRepo->expects($this->once())
            ->method('findOrFail')
            ->with(99)
            ->willReturn($model);

        $service = $this->makeService(deptRepo: $deptRepo);
        $result  = $service->showDepartment(99);

        $this->assertSame($model, $result);
    }
}
