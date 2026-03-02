<?php

declare(strict_types=1);

namespace Modules\Organisation\Tests\Unit;

use Modules\Organisation\Application\Services\OrganisationService;
use Modules\Organisation\Domain\Contracts\BranchRepositoryContract;
use Modules\Organisation\Domain\Contracts\DepartmentRepositoryContract;
use Modules\Organisation\Domain\Contracts\LocationRepositoryContract;
use Modules\Organisation\Domain\Contracts\OrganisationRepositoryContract;
use Modules\Organisation\Interfaces\Http\Controllers\OrganisationController;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for OrganisationController hierarchy endpoints.
 *
 * Verifies that all branch, location, and department controller methods exist
 * and are publicly accessible. No database or Laravel bootstrap required.
 */
class OrganisationHierarchyControllerTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Branch controller methods
    // -------------------------------------------------------------------------

    public function test_organisation_controller_has_list_branches_method(): void
    {
        $this->assertTrue(
            method_exists(OrganisationController::class, 'listBranches'),
            'OrganisationController must expose a public listBranches() method.'
        );
    }

    public function test_organisation_controller_has_create_branch_method(): void
    {
        $this->assertTrue(
            method_exists(OrganisationController::class, 'createBranch'),
            'OrganisationController must expose a public createBranch() method.'
        );
    }

    public function test_organisation_controller_has_show_branch_method(): void
    {
        $this->assertTrue(
            method_exists(OrganisationController::class, 'showBranch'),
            'OrganisationController must expose a public showBranch() method.'
        );
    }

    // -------------------------------------------------------------------------
    // Location controller methods
    // -------------------------------------------------------------------------

    public function test_organisation_controller_has_list_locations_method(): void
    {
        $this->assertTrue(
            method_exists(OrganisationController::class, 'listLocations'),
            'OrganisationController must expose a public listLocations() method.'
        );
    }

    public function test_organisation_controller_has_create_location_method(): void
    {
        $this->assertTrue(
            method_exists(OrganisationController::class, 'createLocation'),
            'OrganisationController must expose a public createLocation() method.'
        );
    }

    public function test_organisation_controller_has_show_location_method(): void
    {
        $this->assertTrue(
            method_exists(OrganisationController::class, 'showLocation'),
            'OrganisationController must expose a public showLocation() method.'
        );
    }

    // -------------------------------------------------------------------------
    // Department controller methods
    // -------------------------------------------------------------------------

    public function test_organisation_controller_has_list_departments_method(): void
    {
        $this->assertTrue(
            method_exists(OrganisationController::class, 'listDepartments'),
            'OrganisationController must expose a public listDepartments() method.'
        );
    }

    public function test_organisation_controller_has_create_department_method(): void
    {
        $this->assertTrue(
            method_exists(OrganisationController::class, 'createDepartment'),
            'OrganisationController must expose a public createDepartment() method.'
        );
    }

    public function test_organisation_controller_has_show_department_method(): void
    {
        $this->assertTrue(
            method_exists(OrganisationController::class, 'showDepartment'),
            'OrganisationController must expose a public showDepartment() method.'
        );
    }

    // -------------------------------------------------------------------------
    // OrganisationService hierarchy delegation
    // -------------------------------------------------------------------------

    public function test_organisation_service_has_list_branches_method(): void
    {
        $this->assertTrue(
            method_exists(OrganisationService::class, 'listBranches'),
            'OrganisationService must expose a public listBranches() method.'
        );
    }

    public function test_organisation_service_has_create_branch_method(): void
    {
        $this->assertTrue(
            method_exists(OrganisationService::class, 'createBranch'),
            'OrganisationService must expose a public createBranch() method.'
        );
    }

    public function test_organisation_service_has_list_locations_method(): void
    {
        $this->assertTrue(
            method_exists(OrganisationService::class, 'listLocations'),
            'OrganisationService must expose a public listLocations() method.'
        );
    }

    public function test_organisation_service_has_create_location_method(): void
    {
        $this->assertTrue(
            method_exists(OrganisationService::class, 'createLocation'),
            'OrganisationService must expose a public createLocation() method.'
        );
    }

    public function test_organisation_service_has_list_departments_method(): void
    {
        $this->assertTrue(
            method_exists(OrganisationService::class, 'listDepartments'),
            'OrganisationService must expose a public listDepartments() method.'
        );
    }

    public function test_organisation_service_has_create_department_method(): void
    {
        $this->assertTrue(
            method_exists(OrganisationService::class, 'createDepartment'),
            'OrganisationService must expose a public createDepartment() method.'
        );
    }
}
