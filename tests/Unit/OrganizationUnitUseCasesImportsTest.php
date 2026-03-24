<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Modules\OrganizationUnit\Application\UseCases\CreateOrganizationUnit;
use Modules\OrganizationUnit\Application\UseCases\GetOrganizationUnit;
use Modules\OrganizationUnit\Application\UseCases\ListOrganizationUnits;
use Modules\OrganizationUnit\Application\UseCases\UpdateOrganizationUnit;
use Modules\OrganizationUnit\Application\UseCases\DeleteOrganizationUnit;
use Modules\OrganizationUnit\Application\UseCases\MoveOrganizationUnit;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitCreated;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitUpdated;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitDeleted;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitMoved;

class OrganizationUnitUseCasesImportsTest extends TestCase
{
    /**
     * Verify that all OrganizationUnit use case classes can be loaded without fatal errors.
     */
    public function test_all_organization_unit_use_case_classes_exist(): void
    {
        $this->assertTrue(class_exists(CreateOrganizationUnit::class));
        $this->assertTrue(class_exists(GetOrganizationUnit::class));
        $this->assertTrue(class_exists(ListOrganizationUnits::class));
        $this->assertTrue(class_exists(UpdateOrganizationUnit::class));
        $this->assertTrue(class_exists(DeleteOrganizationUnit::class));
        $this->assertTrue(class_exists(MoveOrganizationUnit::class));
    }

    public function test_all_organization_unit_event_classes_exist(): void
    {
        $this->assertTrue(class_exists(OrganizationUnitCreated::class));
        $this->assertTrue(class_exists(OrganizationUnitUpdated::class));
        $this->assertTrue(class_exists(OrganizationUnitDeleted::class));
        $this->assertTrue(class_exists(OrganizationUnitMoved::class));
    }
}
