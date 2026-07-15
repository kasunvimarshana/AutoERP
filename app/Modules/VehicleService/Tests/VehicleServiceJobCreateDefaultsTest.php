<?php

declare(strict_types=1);

namespace Modules\VehicleService\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\VehicleService\Constants\VehicleServicePermission;
use Modules\VehicleService\Enums\VehicleServiceCommissionType;
use Modules\VehicleService\Http\Controllers\VehicleServiceJobController;
use Modules\VehicleService\Http\Requests\ListVehicleServiceJobRequest;
use Modules\VehicleService\Services\VehicleServiceCommissionPolicyService;
use Tests\Support\OrganizationUnitFixture;
use Tests\TestCase;

final class VehicleServiceJobCreateDefaultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_creator_can_load_the_resolved_supervisor_commission_default(): void
    {
        $context = $this->context();
        $this->withTenantExecutionContext(
            $context['tenant_id'],
            fn () => app(VehicleServiceCommissionPolicyService::class)->saveSupervisorDefault(
                $context['tenant_id'],
                $context['organization_unit_id'],
                VehicleServiceCommissionType::Percentage,
                '7.5',
                true,
                null,
                null,
            ),
        );

        $request = ListVehicleServiceJobRequest::create(
            '/api/v1/vehicle-service/jobs/create-defaults',
            'GET',
        );
        $request->attributes->set(
            (string) config('core.current_tenant.id_attribute', 'current_tenant_id'),
            $context['tenant_id'],
        );
        $request->attributes->set(
            (string) config('core.current_organization_unit.id_attribute', 'current_organization_unit_id'),
            $context['organization_unit_id'],
        );

        $response = $this->withTenantExecutionContext(
            $context['tenant_id'],
            fn () => app(VehicleServiceJobController::class)->createDefaults(
                $request,
                app(VehicleServiceCommissionPolicyService::class),
            ),
        );

        $this->assertSame([
            'data' => [
                'commission_type' => 'percentage',
                'commission_value' => '7.500000',
            ],
        ], $response->getData(true));

        $route = app('router')->getRoutes()->getByName('api.v1.vehicle-service.jobs.create-defaults');
        $this->assertNotNull($route);
        $middleware = $route->gatherMiddleware();
        $permissionMiddleware = (string) config('user.tenant.permission_middleware_alias', 'tenant.permission');
        $this->assertContains($permissionMiddleware.':'.VehicleServicePermission::JOBS_CREATE, $middleware);
        $this->assertNotContains($permissionMiddleware.':'.VehicleServicePermission::COMMISSIONS_VIEW, $middleware);
    }

    /** @return array{tenant_id:int, organization_unit_id:int} */
    private function context(): array
    {
        $now = now();
        $code = 'VSJ-'.Str::upper(Str::random(6));
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => $code,
            'name' => $code.' Tenant',
            'slug' => Str::lower($code).'-tenant',
            'status' => 'active',
            'row_version' => 1,
            'status_reason' => 'Vehicle Service job create defaults test.',
            'status_changed_at' => $now,
            'activated_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $organizationUnitId = (int) OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => 'Main Workshop',
            'code' => 'WORKSHOP',
            'path' => '/workshop',
            'is_active' => true,
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
        ];
    }
}
