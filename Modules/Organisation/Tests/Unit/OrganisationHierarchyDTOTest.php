<?php

declare(strict_types=1);

namespace Modules\Organisation\Tests\Unit;

use Modules\Organisation\Application\DTOs\CreateBranchDTO;
use Modules\Organisation\Application\DTOs\CreateDepartmentDTO;
use Modules\Organisation\Application\DTOs\CreateLocationDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Organisation hierarchy DTOs.
 *
 * Covers: CreateBranchDTO, CreateLocationDTO, CreateDepartmentDTO.
 * Pure PHP — no database or Laravel bootstrap required.
 */
class OrganisationHierarchyDTOTest extends TestCase
{
    // -------------------------------------------------------------------------
    // CreateBranchDTO
    // -------------------------------------------------------------------------

    public function test_branch_dto_from_array_hydrates_all_fields(): void
    {
        $dto = CreateBranchDTO::fromArray([
            'organisation_id' => 1,
            'name'            => 'Head Office',
            'code'            => 'HO',
            'address'         => '1 Enterprise Way',
            'is_active'       => true,
        ]);

        $this->assertSame(1, $dto->organisationId);
        $this->assertSame('Head Office', $dto->name);
        $this->assertSame('HO', $dto->code);
        $this->assertSame('1 Enterprise Way', $dto->address);
        $this->assertTrue($dto->isActive);
    }

    public function test_branch_dto_address_defaults_to_null(): void
    {
        $dto = CreateBranchDTO::fromArray([
            'organisation_id' => 2,
            'name'            => 'Remote Office',
            'code'            => 'RO',
        ]);

        $this->assertNull($dto->address);
    }

    public function test_branch_dto_is_active_defaults_to_true(): void
    {
        $dto = CreateBranchDTO::fromArray([
            'organisation_id' => 2,
            'name'            => 'New Branch',
            'code'            => 'NB',
        ]);

        $this->assertTrue($dto->isActive);
    }

    public function test_branch_dto_is_active_can_be_false(): void
    {
        $dto = CreateBranchDTO::fromArray([
            'organisation_id' => 3,
            'name'            => 'Closed Branch',
            'code'            => 'CB',
            'is_active'       => false,
        ]);

        $this->assertFalse($dto->isActive);
    }

    public function test_branch_dto_accepts_string_organisation_id(): void
    {
        $dto = CreateBranchDTO::fromArray([
            'organisation_id' => 'uuid-org-123',
            'name'            => 'Branch X',
            'code'            => 'BX',
        ]);

        $this->assertSame('uuid-org-123', $dto->organisationId);
    }

    // -------------------------------------------------------------------------
    // CreateLocationDTO
    // -------------------------------------------------------------------------

    public function test_location_dto_from_array_hydrates_all_fields(): void
    {
        $dto = CreateLocationDTO::fromArray([
            'branch_id'   => 5,
            'name'        => 'Main Warehouse',
            'code'        => 'MW',
            'description' => 'Primary storage facility',
            'is_active'   => true,
        ]);

        $this->assertSame(5, $dto->branchId);
        $this->assertSame('Main Warehouse', $dto->name);
        $this->assertSame('MW', $dto->code);
        $this->assertSame('Primary storage facility', $dto->description);
        $this->assertTrue($dto->isActive);
    }

    public function test_location_dto_description_defaults_to_null(): void
    {
        $dto = CreateLocationDTO::fromArray([
            'branch_id' => 5,
            'name'      => 'Store Front',
            'code'      => 'SF',
        ]);

        $this->assertNull($dto->description);
    }

    public function test_location_dto_is_active_defaults_to_true(): void
    {
        $dto = CreateLocationDTO::fromArray([
            'branch_id' => 5,
            'name'      => 'New Location',
            'code'      => 'NL',
        ]);

        $this->assertTrue($dto->isActive);
    }

    public function test_location_dto_is_active_can_be_false(): void
    {
        $dto = CreateLocationDTO::fromArray([
            'branch_id' => 5,
            'name'      => 'Archived Location',
            'code'      => 'AL',
            'is_active' => false,
        ]);

        $this->assertFalse($dto->isActive);
    }

    public function test_location_dto_accepts_string_branch_id(): void
    {
        $dto = CreateLocationDTO::fromArray([
            'branch_id' => 'uuid-branch-456',
            'name'      => 'Location Y',
            'code'      => 'LY',
        ]);

        $this->assertSame('uuid-branch-456', $dto->branchId);
    }

    // -------------------------------------------------------------------------
    // CreateDepartmentDTO
    // -------------------------------------------------------------------------

    public function test_department_dto_from_array_hydrates_all_fields(): void
    {
        $dto = CreateDepartmentDTO::fromArray([
            'location_id' => 10,
            'name'        => 'Accounts Payable',
            'code'        => 'AP',
            'is_active'   => true,
        ]);

        $this->assertSame(10, $dto->locationId);
        $this->assertSame('Accounts Payable', $dto->name);
        $this->assertSame('AP', $dto->code);
        $this->assertTrue($dto->isActive);
    }

    public function test_department_dto_is_active_defaults_to_true(): void
    {
        $dto = CreateDepartmentDTO::fromArray([
            'location_id' => 10,
            'name'        => 'HR',
            'code'        => 'HR',
        ]);

        $this->assertTrue($dto->isActive);
    }

    public function test_department_dto_is_active_can_be_false(): void
    {
        $dto = CreateDepartmentDTO::fromArray([
            'location_id' => 10,
            'name'        => 'Archived Dept',
            'code'        => 'AD',
            'is_active'   => false,
        ]);

        $this->assertFalse($dto->isActive);
    }

    public function test_department_dto_accepts_string_location_id(): void
    {
        $dto = CreateDepartmentDTO::fromArray([
            'location_id' => 'uuid-loc-789',
            'name'        => 'Finance',
            'code'        => 'FIN',
        ]);

        $this->assertSame('uuid-loc-789', $dto->locationId);
    }
}
