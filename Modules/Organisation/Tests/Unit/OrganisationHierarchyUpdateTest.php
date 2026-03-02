<?php

declare(strict_types=1);

namespace Modules\Organisation\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Modules\Organisation\Application\Services\OrganisationService;
use Modules\Organisation\Domain\Contracts\BranchRepositoryContract;
use Modules\Organisation\Domain\Contracts\DepartmentRepositoryContract;
use Modules\Organisation\Domain\Contracts\LocationRepositoryContract;
use Modules\Organisation\Domain\Contracts\OrganisationRepositoryContract;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Structural compliance tests for OrganisationService update/delete methods.
 *
 * Verifies that the hierarchy update and delete methods exist, are public,
 * and have the correct signatures. These are pure-PHP structural tests
 * that require no Laravel bootstrap.
 */
class OrganisationHierarchyUpdateTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Method existence — updateBranch / deleteBranch
    // -------------------------------------------------------------------------

    public function test_service_has_update_branch_method(): void
    {
        $this->assertTrue(method_exists(OrganisationService::class, 'updateBranch'));
    }

    public function test_service_has_delete_branch_method(): void
    {
        $this->assertTrue(method_exists(OrganisationService::class, 'deleteBranch'));
    }

    // -------------------------------------------------------------------------
    // Method existence — updateLocation / deleteLocation
    // -------------------------------------------------------------------------

    public function test_service_has_update_location_method(): void
    {
        $this->assertTrue(method_exists(OrganisationService::class, 'updateLocation'));
    }

    public function test_service_has_delete_location_method(): void
    {
        $this->assertTrue(method_exists(OrganisationService::class, 'deleteLocation'));
    }

    // -------------------------------------------------------------------------
    // Method existence — updateDepartment / deleteDepartment
    // -------------------------------------------------------------------------

    public function test_service_has_update_department_method(): void
    {
        $this->assertTrue(method_exists(OrganisationService::class, 'updateDepartment'));
    }

    public function test_service_has_delete_department_method(): void
    {
        $this->assertTrue(method_exists(OrganisationService::class, 'deleteDepartment'));
    }

    // -------------------------------------------------------------------------
    // Visibility — all public
    // -------------------------------------------------------------------------

    public function test_update_branch_is_public(): void
    {
        $ref = new ReflectionMethod(OrganisationService::class, 'updateBranch');
        $this->assertTrue($ref->isPublic());
    }

    public function test_delete_branch_is_public(): void
    {
        $ref = new ReflectionMethod(OrganisationService::class, 'deleteBranch');
        $this->assertTrue($ref->isPublic());
    }

    // -------------------------------------------------------------------------
    // Signatures — updateBranch
    // -------------------------------------------------------------------------

    public function test_update_branch_accepts_id_and_data(): void
    {
        $ref    = new ReflectionMethod(OrganisationService::class, 'updateBranch');
        $params = $ref->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('data', $params[1]->getName());
    }

    // -------------------------------------------------------------------------
    // Signatures — deleteBranch
    // -------------------------------------------------------------------------

    public function test_delete_branch_accepts_id(): void
    {
        $ref    = new ReflectionMethod(OrganisationService::class, 'deleteBranch');
        $params = $ref->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    public function test_delete_branch_return_type_is_bool(): void
    {
        $ref        = new ReflectionMethod(OrganisationService::class, 'deleteBranch');
        $returnType = (string) $ref->getReturnType();

        $this->assertSame('bool', $returnType);
    }

    // -------------------------------------------------------------------------
    // Signatures — updateLocation / deleteLocation
    // -------------------------------------------------------------------------

    public function test_update_location_accepts_id_and_data(): void
    {
        $ref    = new ReflectionMethod(OrganisationService::class, 'updateLocation');
        $params = $ref->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('data', $params[1]->getName());
    }

    public function test_delete_location_return_type_is_bool(): void
    {
        $ref        = new ReflectionMethod(OrganisationService::class, 'deleteLocation');
        $returnType = (string) $ref->getReturnType();

        $this->assertSame('bool', $returnType);
    }

    // -------------------------------------------------------------------------
    // Signatures — updateDepartment / deleteDepartment
    // -------------------------------------------------------------------------

    public function test_update_department_accepts_id_and_data(): void
    {
        $ref    = new ReflectionMethod(OrganisationService::class, 'updateDepartment');
        $params = $ref->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('data', $params[1]->getName());
    }
}
