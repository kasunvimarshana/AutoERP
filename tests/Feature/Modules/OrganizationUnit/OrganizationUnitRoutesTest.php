<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\OrganizationUnit;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class OrganizationUnitRoutesTest extends TestCase
{
    public function test_organization_unit_routes_are_registered(): void
    {
        self::assertTrue(Route::has('organization-unit.organization-unit-types.index'));
        self::assertTrue(Route::has('organization-unit.organization-units.index'));
        self::assertTrue(Route::has('organization-unit.organization-unit-setting-groups.index'));
        self::assertTrue(Route::has('organization-unit.organization-unit-settings.index'));
        self::assertTrue(Route::has('organization-unit.organization-unit-documents.index'));
    }
}
