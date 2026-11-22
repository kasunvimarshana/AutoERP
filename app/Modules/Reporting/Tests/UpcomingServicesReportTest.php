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

use Carbon\CarbonImmutable;
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
use Modules\VehicleService\Services\VehicleServiceJobService;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Tests\Support\OrganizationUnitFixture;
use Tests\Support\TenantUserFixture;
use Tests\TestCase;

final class UpcomingServicesReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
    }

    public function test_upcoming_services_report_returns_accurate_structure_and_counts(): void
    {
        $context = $this->context('UP1');
        $this->authorize($context);

        $now = CarbonImmutable::now();
        $today = $now->toDateString();

        // 1. Overdue (5 days ago)
        $vehicle1 = $this->vehicle($context['tenant_id'], $context['organization_unit_id'], $context['customer_id'], 'UP1-V1');
        $jobOverdue = $this->createJobWithNextDate($context, $vehicle1, $now->subMonths(2)->toDateString(), $now->subDays(5)->toDateString());

        // 2. Due today
        $vehicle2 = $this->vehicle($context['tenant_id'], $context['organization_unit_id'], $context['customer_id'], 'UP1-V2');
        $jobDueToday = $this->createJobWithNextDate($context, $vehicle2, $now->subMonth()->toDateString(), $today);

        // 3. Due this week (in 3 days)
        $vehicle3 = $this->vehicle($context['tenant_id'], $context['organization_unit_id'], $context['customer_id'], 'UP1-V3');
        $jobThisWeek = $this->createJobWithNextDate($context, $vehicle3, $today, $now->addDays(3)->toDateString());

        // 4. Due this month (in 20 days)
        $vehicle4 = $this->vehicle($context['tenant_id'], $context['organization_unit_id'], $context['customer_id'], 'UP1-V4');
        $jobThisMonth = $this->createJobWithNextDate($context, $vehicle4, $today, $now->addDays(20)->toDateString());

        // 5. Upcoming (> 30 days, in 45 days)
        $vehicle5 = $this->vehicle($context['tenant_id'], $context['organization_unit_id'], $context['customer_id'], 'UP1-V5');
        $jobUpcoming = $this->createJobWithNextDate($context, $vehicle5, $today, $now->addDays(45)->toDateString());

        $response = $this->reportGetJson($context, '/api/v1/reports/vehicle-service/upcoming-services?'.http_build_query([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
        ]));

        $response->assertOk()
            ->assertJsonPath('summary.total_upcoming', 5)
            ->assertJsonPath('summary.total_overdue', 1)
            ->assertJsonPath('summary.total_due_today', 1)
            ->assertJsonPath('summary.total_due_this_week', 1)
            ->assertJsonPath('summary.total_due_this_month', 1)
            ->assertJsonPath('meta.total', 5);

        $data = collect($response->json('data'));

        // Verify overdue job
        $rowOverdue = $data->firstWhere('id', (int) $jobOverdue->getKey());
        $this->assertNotNull($rowOverdue);
        $this->assertSame('overdue', $rowOverdue['urgency_status']);
        $this->assertSame(5, $rowOverdue['days_overdue']);
        $this->assertSame('5 days overdue', $rowOverdue['urgency_label']);
        $this->assertSame('REG-UP1-V1', $rowOverdue['registration_number']);

        // Verify due today job
        $rowDueToday = $data->firstWhere('id', (int) $jobDueToday->getKey());
        $this->assertNotNull($rowDueToday);
        $this->assertSame('due_today', $rowDueToday['urgency_status']);
        $this->assertSame(0, $rowDueToday['days_overdue']);
        $this->assertSame('Due today', $rowDueToday['urgency_label']);

        // Verify due this week job
        $rowThisWeek = $data->firstWhere('id', (int) $jobThisWeek->getKey());
        $this->assertNotNull($rowThisWeek);
        $this->assertSame('due_this_week', $rowThisWeek['urgency_status']);
        $this->assertSame(3, $rowThisWeek['days_until_due']);
        $this->assertSame('Due in 3 days', $rowThisWeek['urgency_label']);

        // Verify due this month job
        $rowThisMonth = $data->firstWhere('id', (int) $jobThisMonth->getKey());
        $this->assertNotNull($rowThisMonth);
        $this->assertSame('due_this_month', $rowThisMonth['urgency_status']);
        $this->assertSame(20, $rowThisMonth['days_until_due']);
        $this->assertSame('Due in 20 days', $rowThisMonth['urgency_label']);

        // Verify upcoming job
        $rowUpcoming = $data->firstWhere('id', (int) $jobUpcoming->getKey());
        $this->assertNotNull($rowUpcoming);
        $this->assertSame('upcoming', $rowUpcoming['urgency_status']);
        $this->assertSame(45, $rowUpcoming['days_until_due']);
        $this->assertSame('Due in 45 days', $rowUpcoming['urgency_label']);
    }

    public function test_upcoming_services_report_filters_by_preset_and_custom_dates(): void
    {
        $context = $this->context('UP2');
        $this->authorize($context);

        $now = CarbonImmutable::now();
        $today = $now->toDateString();

        $vehicle1 = $this->vehicle($context['tenant_id'], $context['organization_unit_id'], $context['customer_id'], 'UP2-V1');
        $jobOverdue = $this->createJobWithNextDate($context, $vehicle1, $now->subMonths(2)->toDateString(), $now->subDays(10)->toDateString());

        $vehicle2 = $this->vehicle($context['tenant_id'], $context['organization_unit_id'], $context['customer_id'], 'UP2-V2');
        $jobDueToday = $this->createJobWithNextDate($context, $vehicle2, $now->subMonth()->toDateString(), $today);

        $vehicle3 = $this->vehicle($context['tenant_id'], $context['organization_unit_id'], $context['customer_id'], 'UP2-V3');
        $jobThisWeek = $this->createJobWithNextDate($context, $vehicle3, $today, $now->addDays(4)->toDateString());

        // Filter overdue
        $resOverdue = $this->reportGetJson($context, '/api/v1/reports/vehicle-service/upcoming-services?'.http_build_query([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'status_preset' => 'overdue',
        ]));
        $resOverdue->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', (int) $jobOverdue->getKey());

        // Filter due_today
        $resToday = $this->reportGetJson($context, '/api/v1/reports/vehicle-service/upcoming-services?'.http_build_query([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'status_preset' => 'due_today',
        ]));
        $resToday->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', (int) $jobDueToday->getKey());

        // Filter due_this_week
        $resWeek = $this->reportGetJson($context, '/api/v1/reports/vehicle-service/upcoming-services?'.http_build_query([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'status_preset' => 'due_this_week',
        ]));
        $resWeek->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', (int) $jobThisWeek->getKey());

        // Filter custom date range
        $resRange = $this->reportGetJson($context, '/api/v1/reports/vehicle-service/upcoming-services?'.http_build_query([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'date_from' => $now->subDays(15)->toDateString(),
            'date_to' => $now->subDays(5)->toDateString(),
        ]));
        $resRange->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', (int) $jobOverdue->getKey());
    }

    public function test_upcoming_services_report_filters_by_vehicle_and_customer(): void
    {
        $context = $this->context('UP3');
        $this->authorize($context);

        $now = CarbonImmutable::now();
        $vehicle1 = $this->vehicle($context['tenant_id'], $context['organization_unit_id'], $context['customer_id'], 'UP3-V1');
        $job1 = $this->createJobWithNextDate($context, $vehicle1, $now->toDateString(), $now->addDays(10)->toDateString());

        $customer2 = $this->customer($context['tenant_id'], $context['organization_unit_id'], 'CUS-UP3-ALT');
        $vehicle2 = $this->vehicle($context['tenant_id'], $context['organization_unit_id'], $customer2, 'UP3-V2');
        $job2 = $this->createJobWithNextDate([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'customer_id' => $customer2,
        ], $vehicle2, $now->toDateString(), $now->addDays(12)->toDateString());

        // Filter by vehicle1
        $resVeh = $this->reportGetJson($context, '/api/v1/reports/vehicle-service/upcoming-services?'.http_build_query([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'vehicle_id' => $vehicle1,
        ]));
        $resVeh->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', (int) $job1->getKey());

        // Filter by customer2
        $resCus = $this->reportGetJson($context, '/api/v1/reports/vehicle-service/upcoming-services?'.http_build_query([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'customer_id' => $customer2,
        ]));
        $resCus->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', (int) $job2->getKey());
    }

    public function test_upcoming_services_report_deduplicates_superseded_jobs_when_latest_only_is_true(): void
    {
        $context = $this->context('UP4');
        $this->authorize($context);

        $vehicle = $this->vehicle($context['tenant_id'], $context['organization_unit_id'], $context['customer_id'], 'UP4-V1');

        // Older job for this vehicle
        $jobOld = $this->createJobWithNextDate($context, $vehicle, '2026-01-01', '2026-02-01');

        // Newer job for the same vehicle
        $jobNew = $this->createJobWithNextDate($context, $vehicle, '2026-02-01', '2026-07-01');

        // With latest_only = true (default)
        $resDefault = $this->reportGetJson($context, '/api/v1/reports/vehicle-service/upcoming-services?'.http_build_query([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
        ]));
        $resDefault->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', (int) $jobNew->getKey());

        // With latest_only = false
        $resAll = $this->reportGetJson($context, '/api/v1/reports/vehicle-service/upcoming-services?'.http_build_query([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'latest_only' => 0,
        ]));
        $resAll->assertOk()->assertJsonPath('meta.total', 2);
    }

    public function test_upcoming_services_report_search_matches_registration_and_customer_name(): void
    {
        $context = $this->context('UP5');
        $this->authorize($context);

        $now = CarbonImmutable::now();
        $vehicle1 = $this->vehicle($context['tenant_id'], $context['organization_unit_id'], $context['customer_id'], 'REG-SPECIAL-99');
        $job1 = $this->createJobWithNextDate($context, $vehicle1, $now->toDateString(), $now->addDays(14)->toDateString());

        // Search vehicle registration
        $resReg = $this->reportGetJson($context, '/api/v1/reports/vehicle-service/upcoming-services?'.http_build_query([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'search' => 'SPECIAL-99',
        ]));
        $resReg->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', (int) $job1->getKey());

        // Search customer name
        $resCus = $this->reportGetJson($context, '/api/v1/reports/vehicle-service/upcoming-services?'.http_build_query([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'search' => 'Customer CUS-UP5',
        ]));
        $resCus->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', (int) $job1->getKey());
    }

    public function test_upcoming_services_report_enforces_tenant_isolation(): void
    {
        $contextA = $this->context('UPA');
        $this->authorize($contextA);

        $now = CarbonImmutable::now();
        $vehicleA = $this->vehicle($contextA['tenant_id'], $contextA['organization_unit_id'], $contextA['customer_id'], 'UPA-V1');
        $jobA = $this->createJobWithNextDate($contextA, $vehicleA, $now->toDateString(), $now->addDays(10)->toDateString());

        // Under Tenant A:
        $responseA = $this->reportGetJson($contextA, '/api/v1/reports/vehicle-service/upcoming-services?'.http_build_query([
            'tenant_id' => $contextA['tenant_id'],
            'organization_unit_id' => $contextA['organization_unit_id'],
        ]));

        $responseA->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', (int) $jobA->getKey())
            ->assertJsonPath('data.0.vehicle.registration_number', 'REG-UPA-V1');

        $contextB = $this->context('UPB');
        $this->authorize($contextB);
        $vehicleB = $this->vehicle($contextB['tenant_id'], $contextB['organization_unit_id'], $contextB['customer_id'], 'UPB-V1');
        $jobB = $this->createJobWithNextDate($contextB, $vehicleB, $now->toDateString(), $now->addDays(10)->toDateString());

        // Under Tenant B:
        $responseB = $this->reportGetJson($contextB, '/api/v1/reports/vehicle-service/upcoming-services?'.http_build_query([
            'tenant_id' => $contextB['tenant_id'],
            'organization_unit_id' => $contextB['organization_unit_id'],
        ]));

        $responseB->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', (int) $jobB->getKey())
            ->assertJsonPath('data.0.vehicle.registration_number', 'REG-UPB-V1');
    }

    public function test_upcoming_services_report_export_csv_and_pdf_succeeds(): void
    {
        $context = $this->context('UPEXP');
        $this->authorize($context);

        $now = CarbonImmutable::now();
        $vehicle = $this->vehicle($context['tenant_id'], $context['organization_unit_id'], $context['customer_id'], 'UPEXP-V1');
        $this->createJobWithNextDate($context, $vehicle, $now->toDateString(), $now->addDays(10)->toDateString());

        // CSV export
        $csvRes = $this->reportGet($context, '/api/v1/reports/vehicle-service/upcoming-services/export/csv?'.http_build_query([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
        ]));
        $csvRes->assertOk();
        $this->assertStringContainsString('REG-UPEXP-V1', (string) $csvRes->getContent());

        // PDF export
        Pdf::fake();
        $pdfRes = $this->reportGet($context, '/api/v1/reports/vehicle-service/upcoming-services/export/pdf?'.http_build_query([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
        ]));
        $pdfRes->assertOk();
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
    private function createJobWithNextDate(
        array $context,
        int $vehicleId,
        string $jobDate,
        string $nextServiceDate,
    ): VehicleServiceJob {
        return $this->withTenantExecutionContext((int) $context['tenant_id'], function () use ($context, $vehicleId, $jobDate, $nextServiceDate): VehicleServiceJob {
            return app(VehicleServiceJobService::class)->create(new VehicleServiceJobData(
                tenantId: (int) $context['tenant_id'],
                jobDate: $jobDate,
                customerId: (int) $context['customer_id'],
                vehicleId: $vehicleId,
                organizationUnitId: $context['organization_unit_id'] ?? null,
                odometerReading: '15000.000000',
                nextServiceDate: $nextServiceDate,
                nextServiceDateProvided: true,
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
        ));
        $context['user_id'] = $userId;
    }
}
