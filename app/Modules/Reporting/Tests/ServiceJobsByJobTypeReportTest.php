<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Reporting\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Modules\Reporting\Services\ReportingAuthorizationService;
use Modules\User\Constants\UserGuard;
use Modules\User\Constants\UserSystemRole;
use Modules\User\Models\UserModel;
use Modules\VehicleService\DTOs\VehicleServiceJobData;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceJobType;
use Modules\VehicleService\Services\VehicleServiceJobService;
use Modules\VehicleService\Services\VehicleServiceJobTypeService;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Tests\Support\OrganizationUnitFixture;
use Tests\Support\TenantUserFixture;
use Tests\TestCase;

final class ServiceJobsByJobTypeReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
    }

    public function test_service_jobs_by_job_type_report_returns_accurate_groups_and_metrics(): void
    {
        $context = $this->context('GRP1');
        $this->authorize($context);

        [$fullServiceType, $oilChangeType] = $this->withTenantExecutionContext($context['tenant_id'], function () use ($context): array {
            app(VehicleServiceJobTypeService::class)->ensureDefaults($context['tenant_id'], $context['organization_unit_id']);
            $jobTypes = VehicleServiceJobType::query()->where('tenant_id', $context['tenant_id'])->get()->keyBy('code');

            return [$jobTypes['full_service'], $jobTypes['oil_change']];
        });

        $vehicle1 = $this->vehicle($context['tenant_id'], $context['organization_unit_id'], $context['customer_id'], 'GRP-V1');
        $vehicle2 = $this->vehicle($context['tenant_id'], $context['organization_unit_id'], $context['customer_id'], 'GRP-V2');

        // Create 2 jobs under Full Service (tracks_mileage = true)
        $job1 = $this->createJob($context, $vehicle1, '2026-06-01', (int) $fullServiceType->getKey(), '10000.000000');
        $job2 = $this->createJob($context, $vehicle2, '2026-06-02', (int) $fullServiceType->getKey(), '15000.000000');

        DB::table('vehicle_service_jobs')->where('id', $job1->id)->update([
            'subtotal' => '500.000000',
            'discount_total' => '50.000000',
            'tax_total' => '40.000000',
            'charge_total' => '10.000000',
            'grand_total' => '500.000000',
        ]);
        DB::table('vehicle_service_jobs')->where('id', $job2->id)->update([
            'subtotal' => '700.000000',
            'discount_total' => '20.000000',
            'tax_total' => '60.000000',
            'charge_total' => '0.000000',
            'grand_total' => '740.000000',
        ]);

        // Create 1 job under Oil Change (tracks_mileage = true)
        $job3 = $this->createJob($context, $vehicle1, '2026-06-03', (int) $oilChangeType->getKey(), '12000.000000');
        DB::table('vehicle_service_jobs')->where('id', $job3->id)->update([
            'subtotal' => '300.000000',
            'discount_total' => '0.000000',
            'tax_total' => '20.000000',
            'charge_total' => '0.000000',
            'grand_total' => '320.000000',
        ]);

        $response = $this->reportGetJson($context, '/api/v1/reports/vehicle-service/jobs-by-type?'.http_build_query([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
        ]));

        $response->assertOk();

        // Total: 3 jobs. Grand total: 500 + 740 + 320 = 1560.
        // Discounts: 50 + 20 = 70.
        $response->assertJsonPath('summary.total_jobs', 3)
            ->assertJsonPath('summary.total_revenue', '1560.000000')
            ->assertJsonPath('summary.total_discount', '70.000000')
            ->assertJsonPath('summary.average_job_value', '520.000000');

        // Groups: should have Full Service and Oil Change
        $groups = collect($response->json('groups'));
        $this->assertCount(2, $groups);

        $fullGroup = $groups->firstWhere('job_type_code', 'full_service');
        $this->assertNotNull($fullGroup);
        $this->assertSame(2, $fullGroup['total_jobs']);
        $this->assertSame('1240.000000', $fullGroup['total_revenue']);
        $this->assertSame('620.000000', $fullGroup['average_job_value']);
        $this->assertSame(79.5, $fullGroup['revenue_share_percentage']); // 1240/1560 ~ 79.5%
        $this->assertCount(2, $fullGroup['jobs']);

        $oilGroup = $groups->firstWhere('job_type_code', 'oil_change');
        $this->assertNotNull($oilGroup);
        $this->assertSame(1, $oilGroup['total_jobs']);
        $this->assertSame('320.000000', $oilGroup['total_revenue']);
        $this->assertSame('320.000000', $oilGroup['average_job_value']);
        $this->assertSame(20.5, $oilGroup['revenue_share_percentage']); // 320/1560 ~ 20.5%
        $this->assertCount(1, $oilGroup['jobs']);
    }

    public function test_service_jobs_by_job_type_report_filters_by_job_type(): void
    {
        $context = $this->context('GRP2');
        $this->authorize($context);

        [$fullServiceType, $oilChangeType] = $this->withTenantExecutionContext($context['tenant_id'], function () use ($context): array {
            app(VehicleServiceJobTypeService::class)->ensureDefaults($context['tenant_id'], $context['organization_unit_id']);
            $jobTypes = VehicleServiceJobType::query()->where('tenant_id', $context['tenant_id'])->get()->keyBy('code');

            return [$jobTypes['full_service'], $jobTypes['oil_change']];
        });

        $vehicle = $this->vehicle($context['tenant_id'], $context['organization_unit_id'], $context['customer_id'], 'GRP2-V1');
        $this->createJob($context, $vehicle, '2026-06-01', (int) $fullServiceType->getKey(), '10000.000000');
        $this->createJob($context, $vehicle, '2026-06-02', (int) $oilChangeType->getKey(), '12000.000000');

        // Filter by Full Service ID
        $response = $this->reportGetJson($context, '/api/v1/reports/vehicle-service/jobs-by-type?'.http_build_query([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'job_type_id' => $fullServiceType->getKey(),
        ]));

        $response->assertOk()
            ->assertJsonPath('summary.total_jobs', 1)
            ->assertJsonPath('groups.0.job_type_code', 'full_service');
    }

    public function test_service_jobs_by_job_type_report_filters_by_date_range(): void
    {
        $context = $this->context('GRP3');
        $this->authorize($context);

        $jobType = $this->withTenantExecutionContext($context['tenant_id'], function () use ($context): VehicleServiceJobType {
            app(VehicleServiceJobTypeService::class)->ensureDefaults($context['tenant_id'], $context['organization_unit_id']);

            return VehicleServiceJobType::query()->where('tenant_id', $context['tenant_id'])->where('code', 'full_service')->firstOrFail();
        });

        $vehicle = $this->vehicle($context['tenant_id'], $context['organization_unit_id'], $context['customer_id'], 'GRP3-V1');

        $this->createJob($context, $vehicle, '2026-05-10', (int) $jobType->getKey(), '10000.000000');
        $this->createJob($context, $vehicle, '2026-06-15', (int) $jobType->getKey(), '12000.000000');
        $this->createJob($context, $vehicle, '2026-07-20', (int) $jobType->getKey(), '15000.000000');

        $response = $this->reportGetJson($context, '/api/v1/reports/vehicle-service/jobs-by-type?'.http_build_query([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'date_from' => '2026-06-01',
            'date_to' => '2026-06-30',
        ]));

        $response->assertOk()
            ->assertJsonPath('summary.total_jobs', 1)
            ->assertJsonPath('data.0.job_date', '2026-06-15');
    }

    public function test_service_jobs_by_job_type_report_filters_by_customer_and_vehicle(): void
    {
        $context = $this->context('GRP4');
        $this->authorize($context);

        $jobType = $this->withTenantExecutionContext($context['tenant_id'], function () use ($context): VehicleServiceJobType {
            app(VehicleServiceJobTypeService::class)->ensureDefaults($context['tenant_id'], $context['organization_unit_id']);

            return VehicleServiceJobType::query()->where('tenant_id', $context['tenant_id'])->where('code', 'full_service')->firstOrFail();
        });

        $vehicle1 = $this->vehicle($context['tenant_id'], $context['organization_unit_id'], $context['customer_id'], 'GRP4-V1');
        $vehicle2 = $this->vehicle($context['tenant_id'], $context['organization_unit_id'], $context['customer_id'], 'GRP4-V2');

        $job1 = $this->createJob($context, $vehicle1, '2026-06-10', (int) $jobType->getKey(), '10000.000000');
        $this->createJob($context, $vehicle2, '2026-06-11', (int) $jobType->getKey(), '15000.000000');

        // Filter by vehicle1
        $resVehicle = $this->reportGetJson($context, '/api/v1/reports/vehicle-service/jobs-by-type?'.http_build_query([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'vehicle_id' => $vehicle1,
        ]));

        $resVehicle->assertOk()
            ->assertJsonPath('summary.total_jobs', 1)
            ->assertJsonPath('data.0.id', (int) $job1->getKey());
    }

    public function test_service_jobs_by_job_type_report_search_matches(): void
    {
        $context = $this->context('GRP5');
        $this->authorize($context);

        $jobType = $this->withTenantExecutionContext($context['tenant_id'], function () use ($context): VehicleServiceJobType {
            app(VehicleServiceJobTypeService::class)->ensureDefaults($context['tenant_id'], $context['organization_unit_id']);

            return VehicleServiceJobType::query()->where('tenant_id', $context['tenant_id'])->where('code', 'full_service')->firstOrFail();
        });

        $vehicle = $this->vehicle($context['tenant_id'], $context['organization_unit_id'], $context['customer_id'], 'SPECIAL-SEARCH-PLATE');

        $job = $this->createJob($context, $vehicle, '2026-06-10', (int) $jobType->getKey(), '10000.000000');

        $response = $this->reportGetJson($context, '/api/v1/reports/vehicle-service/jobs-by-type?'.http_build_query([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'search' => 'SEARCH-PLATE',
        ]));

        $response->assertOk()
            ->assertJsonPath('summary.total_jobs', 1)
            ->assertJsonPath('data.0.id', (int) $job->getKey());
    }

    public function test_service_jobs_by_job_type_report_enforces_tenant_isolation(): void
    {
        $contextA = $this->context('GRPA');
        $this->authorize($contextA);

        $jobTypeA = $this->withTenantExecutionContext($contextA['tenant_id'], function () use ($contextA): VehicleServiceJobType {
            app(VehicleServiceJobTypeService::class)->ensureDefaults($contextA['tenant_id'], $contextA['organization_unit_id']);

            return VehicleServiceJobType::query()->where('tenant_id', $contextA['tenant_id'])->where('code', 'full_service')->firstOrFail();
        });

        $vehicleA = $this->vehicle($contextA['tenant_id'], $contextA['organization_unit_id'], $contextA['customer_id'], 'GRPA-V1');
        $jobA = $this->createJob($contextA, $vehicleA, '2026-06-10', (int) $jobTypeA->getKey(), '10000.000000');

        $responseA = $this->reportGetJson($contextA, '/api/v1/reports/vehicle-service/jobs-by-type?'.http_build_query([
            'tenant_id' => $contextA['tenant_id'],
            'organization_unit_id' => $contextA['organization_unit_id'],
        ]));

        $responseA->assertOk()
            ->assertJsonPath('summary.total_jobs', 1)
            ->assertJsonPath('data.0.id', (int) $jobA->getKey());

        $contextB = $this->context('GRPB');
        $this->authorize($contextB);

        $responseB = $this->reportGetJson($contextB, '/api/v1/reports/vehicle-service/jobs-by-type?'.http_build_query([
            'tenant_id' => $contextB['tenant_id'],
            'organization_unit_id' => $contextB['organization_unit_id'],
        ]));

        $responseB->assertOk()
            ->assertJsonPath('summary.total_jobs', 0);
    }

    public function test_service_jobs_by_job_type_report_export_csv_and_pdf(): void
    {
        $context = $this->context('GRPEXP');
        $this->authorize($context);

        $jobType = $this->withTenantExecutionContext($context['tenant_id'], function () use ($context): VehicleServiceJobType {
            app(VehicleServiceJobTypeService::class)->ensureDefaults($context['tenant_id'], $context['organization_unit_id']);

            return VehicleServiceJobType::query()->where('tenant_id', $context['tenant_id'])->where('code', 'full_service')->firstOrFail();
        });

        $vehicle = $this->vehicle($context['tenant_id'], $context['organization_unit_id'], $context['customer_id'], 'GRPEXP-V1');
        $this->createJob($context, $vehicle, '2026-06-10', (int) $jobType->getKey(), '10000.000000');

        // CSV export
        $csvResponse = $this->reportGet($context, '/api/v1/reports/vehicle-service/jobs-by-type/export/csv?'.http_build_query([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
        ]));

        $csvResponse->assertOk();
        $this->assertStringContainsString('text/csv', (string) $csvResponse->headers->get('content-type'));
        $this->assertStringContainsString('REG-GRPEXP-V1', (string) $csvResponse->getContent());

        // PDF export
        Pdf::fake();
        $pdfResponse = $this->reportGet($context, '/api/v1/reports/vehicle-service/jobs-by-type/export/pdf?'.http_build_query([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
        ]));

        $pdfResponse->assertOk();
        Pdf::assertRespondedWithPdf(
            fn (PdfBuilder $pdf): bool => $pdf->viewData['mode'] === 'pdf'
        );
    }

    private function reportGetJson(array $context, string $uri): TestResponse
    {
        return $this->withTenantRequestContext(
            (int) $context['tenant_id'],
            (int) $context['user_id'],
            fn (): TestResponse => $this->getJson($uri),
            (int) $context['organization_unit_id'],
        );
    }

    private function reportGet(array $context, string $uri): TestResponse
    {
        return $this->withTenantRequestContext(
            (int) $context['tenant_id'],
            (int) $context['user_id'],
            fn (): TestResponse => $this->get($uri),
            (int) $context['organization_unit_id'],
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function createJob(
        array $context,
        int $vehicleId,
        string $jobDate,
        int $jobTypeId,
        ?string $odometerReading,
    ): VehicleServiceJob {
        return $this->withTenantExecutionContext((int) $context['tenant_id'], function () use ($context, $vehicleId, $jobDate, $jobTypeId, $odometerReading): VehicleServiceJob {
            return app(VehicleServiceJobService::class)->create(new VehicleServiceJobData(
                tenantId: (int) $context['tenant_id'],
                jobDate: $jobDate,
                customerId: (int) $context['customer_id'],
                vehicleId: $vehicleId,
                jobTypeId: $jobTypeId,
                organizationUnitId: $context['organization_unit_id'] ?? null,
                odometerReading: $odometerReading,
            ));
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function context(string $suffix): array
    {
        $tenantId = $this->tenant($suffix);
        $organizationUnitId = $this->organizationUnit($tenantId, 'ORG-'.$suffix);
        $customerId = $this->customer($tenantId, $organizationUnitId, 'CUS-'.$suffix);

        return [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'customer_id' => $customerId,
        ];
    }

    private function tenant(string $suffix): int
    {
        return (int) DB::table('tenants')->insertGetId([
            'name' => 'Tenant '.$suffix,
            'code' => 'TEN-'.$suffix,
            'slug' => 'tenant-'.strtolower($suffix),
            'uuid' => (string) Str::uuid(),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function organizationUnit(int $tenantId, string $code): int
    {
        return (int) OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => 'Org '.$code,
            'code' => $code,
        ]);
    }

    private function customer(int $tenantId, ?int $organizationUnitId, string $code): int
    {
        return (int) DB::table('customers')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'customer_number' => $code,
            'code' => $code,
            'name' => 'Customer '.$code,
            'display_name' => 'Customer '.$code,
            'customer_type' => 'individual',
            'phone' => '0771234567',
            'email' => strtolower($code).'@example.com',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function vehicle(int $tenantId, ?int $organizationUnitId, int $customerId, string $code): int
    {
        $vehicleId = (int) DB::table('vehicles')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'vehicle_number' => $code,
            'registration_number' => 'REG-'.$code,
            'odometer_reading' => '25000.000000',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vehicle_ownerships')->insert([
            'row_version' => 1,
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'vehicle_id' => $vehicleId,
            'owner_type' => 'customer',
            'owner_id' => $customerId,
            'owner_key' => 'customer:'.$customerId,
            'owner_code_snapshot' => $code,
            'owner_name_snapshot' => 'Customer '.$code,
            'ownership_type' => 'customer_owned',
            'started_at' => now(),
            'is_current' => true,
            'current_guard' => 1,
            'active_guard' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $vehicleId;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function authorize(array &$context): void
    {
        $tenantId = (int) $context['tenant_id'];
        $guard = UserGuard::TENANT_API;
        $now = now();
        $userId = (int) TenantUserFixture::create([
            'tenant_id' => $tenantId,
            'first_name' => 'Reporting',
            'last_name' => 'Administrator',
            'email' => 'reporting-admin-'.Str::lower(Str::random(8)).'@example.test',
            'password' => bcrypt('secret-password'),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $roleId = (int) DB::table('roles')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => UserSystemRole::SUPER_ADMIN_NAME,
            'active_name_key' => mb_strtolower(UserSystemRole::SUPER_ADMIN_NAME),
            'guard_name' => $guard,
            'system_key' => UserSystemRole::SUPER_ADMIN,
            'is_system' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $permissionIds = [];
        foreach (ReportingAuthorizationService::descriptions() as $name => $description) {
            $permissionIds[] = (int) DB::table('permissions')->insertGetId([
                'tenant_id' => $tenantId,
                'name' => $name,
                'guard_name' => $guard,
                'module' => 'Reporting',
                'description' => $description,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($permissionIds as $permissionId) {
            DB::table('role_permissions')->insert([
                'tenant_id' => $tenantId,
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('user_roles')->insert([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'role_id' => $roleId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->actingAs($this->withTenantExecutionContext(
            $tenantId,
            fn (): UserModel => UserModel::query()->findOrFail($userId),
        ), $guard);

        $context['user_id'] = $userId;
    }
}
