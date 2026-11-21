<?php

declare(strict_types=1);

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
use Modules\VehicleService\DTOs\VehicleServiceJobFeedbackData;
use Modules\VehicleService\Enums\VehicleServiceFeedbackType;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceJobType;
use Modules\VehicleService\Services\VehicleServiceJobFeedbackService;
use Modules\VehicleService\Services\VehicleServiceJobService;
use Modules\VehicleService\Services\VehicleServiceJobTypeService;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Tests\Support\OrganizationUnitFixture;
use Tests\Support\TenantUserFixture;
use Tests\TestCase;

final class ServiceJobFeedbackReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
    }

    public function test_service_job_feedback_report_returns_accurate_summary_metrics_and_rating_breakdown(): void
    {
        $context = $this->context('FBR1');
        $this->authorize($context);

        $jobType = $this->ensureJobType($context);
        $vehicle = $this->vehicle($context['tenant_id'], $context['organization_unit_id'], $context['customer_id'], 'FB-V1');
        $job = $this->createJob($context, $vehicle, '2026-06-01', (int) $jobType->getKey());

        // Create 4 feedbacks: 5-star review, 4-star feedback, 1-star complaint, unrated internal note
        $this->createFeedback($context, $job, VehicleServiceFeedbackType::Review, 5, 'Great work', 'Everything was done on time', true, 'Alice');
        $this->createFeedback($context, $job, VehicleServiceFeedbackType::CustomerFeedback, 4, 'Good service', 'Friendly staff', true, 'Bob');
        $this->createFeedback($context, $job, VehicleServiceFeedbackType::Complaint, 1, 'Delayed delivery', 'Was delayed 3 hours', false, 'Charlie');
        $this->createFeedback($context, $job, VehicleServiceFeedbackType::InternalNote, null, null, 'Technician noted worn brake pads', false, 'Staff Dave');

        $response = $this->reportGetJson($context, '/api/v1/reports/vehicle-service/job-feedback?'.http_build_query([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
        ]));

        $response->assertOk();

        // 1 distinct job, 4 total feedbacks
        $response->assertJsonPath('summary.total_jobs', 1)
            ->assertJsonPath('summary.total_feedbacks', 4)
            ->assertJsonPath('summary.total_rated', 3)
            ->assertJsonPath('summary.total_unrated', 1)
            ->assertJsonPath('summary.average_rating', 3.33)
            ->assertJsonPath('summary.reviews_count', 1)
            ->assertJsonPath('summary.customer_feedback_count', 1)
            ->assertJsonPath('summary.complaints_count', 1)
            ->assertJsonPath('summary.internal_notes_count', 1)
            ->assertJsonPath('summary.positive_ratings_count', 2)
            ->assertJsonPath('summary.positive_rating_percentage', 66.7)
            ->assertJsonPath('summary.complaint_rate_percentage', 25)
            ->assertJsonPath('summary.rating_breakdown.5', 1)
            ->assertJsonPath('summary.rating_breakdown.4', 1)
            ->assertJsonPath('summary.rating_breakdown.3', 0)
            ->assertJsonPath('summary.rating_breakdown.2', 0)
            ->assertJsonPath('summary.rating_breakdown.1', 1);

        $data = $response->json('data');
        $this->assertCount(4, $data);
    }

    public function test_service_job_feedback_report_filters_by_feedback_type(): void
    {
        $context = $this->context('FBR2');
        $this->authorize($context);

        $jobType = $this->ensureJobType($context);
        $vehicle = $this->vehicle($context['tenant_id'], $context['organization_unit_id'], $context['customer_id'], 'FB-V2');
        $job = $this->createJob($context, $vehicle, '2026-06-02', (int) $jobType->getKey());

        $this->createFeedback($context, $job, VehicleServiceFeedbackType::Review, 5, 'Superb', 'Super service', true, 'Alice');
        $this->createFeedback($context, $job, VehicleServiceFeedbackType::Complaint, 1, 'Bad', 'Bad service', false, 'Bob');

        $response = $this->reportGetJson($context, '/api/v1/reports/vehicle-service/job-feedback?'.http_build_query([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'feedback_type' => 'complaint',
        ]));

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('complaint', $response->json('data.0.feedback_type'));
        $this->assertSame('Bad', $response->json('data.0.title'));
    }

    public function test_service_job_feedback_report_filters_by_rating(): void
    {
        $context = $this->context('FBR3');
        $this->authorize($context);

        $jobType = $this->ensureJobType($context);
        $vehicle = $this->vehicle($context['tenant_id'], $context['organization_unit_id'], $context['customer_id'], 'FB-V3');
        $job = $this->createJob($context, $vehicle, '2026-06-03', (int) $jobType->getKey());

        $this->createFeedback($context, $job, VehicleServiceFeedbackType::Review, 5, 'Five Star', 'Excellent', true, 'Alice');
        $this->createFeedback($context, $job, VehicleServiceFeedbackType::Review, 3, 'Average', 'Okay', true, 'Bob');
        $this->createFeedback($context, $job, VehicleServiceFeedbackType::InternalNote, null, null, 'Note text', false, 'Dave');

        // Test filter rating = 5
        $res5 = $this->reportGetJson($context, '/api/v1/reports/vehicle-service/job-feedback?'.http_build_query([
            'tenant_id' => $context['tenant_id'],
            'rating' => '5',
        ]));
        $res5->assertOk();
        $this->assertCount(1, $res5->json('data'));
        $this->assertSame(5, $res5->json('data.0.rating'));

        // Test filter rating = unrated
        $resUnrated = $this->reportGetJson($context, '/api/v1/reports/vehicle-service/job-feedback?'.http_build_query([
            'tenant_id' => $context['tenant_id'],
            'rating' => 'unrated',
        ]));
        $resUnrated->assertOk();
        $this->assertCount(1, $resUnrated->json('data'));
        $this->assertNull($resUnrated->json('data.0.rating'));

        // Test filter rating = rated
        $resRated = $this->reportGetJson($context, '/api/v1/reports/vehicle-service/job-feedback?'.http_build_query([
            'tenant_id' => $context['tenant_id'],
            'rating' => 'rated',
        ]));
        $resRated->assertOk();
        $this->assertCount(2, $resRated->json('data'));
    }

    public function test_service_job_feedback_report_filters_by_customer_visibility(): void
    {
        $context = $this->context('FBR4');
        $this->authorize($context);

        $jobType = $this->ensureJobType($context);
        $vehicle = $this->vehicle($context['tenant_id'], $context['organization_unit_id'], $context['customer_id'], 'FB-V4');
        $job = $this->createJob($context, $vehicle, '2026-06-04', (int) $jobType->getKey());

        $this->createFeedback($context, $job, VehicleServiceFeedbackType::Review, 5, 'Public', 'Visible to client', true, 'Alice');
        $this->createFeedback($context, $job, VehicleServiceFeedbackType::InternalNote, null, null, 'Private staff note', false, 'Dave');

        // Test filter customer_visible
        $resVisible = $this->reportGetJson($context, '/api/v1/reports/vehicle-service/job-feedback?'.http_build_query([
            'tenant_id' => $context['tenant_id'],
            'is_customer_visible' => 'customer_visible',
        ]));
        $resVisible->assertOk();
        $this->assertCount(1, $resVisible->json('data'));
        $this->assertTrue($resVisible->json('data.0.is_customer_visible'));

        // Test filter internal_only
        $resInternal = $this->reportGetJson($context, '/api/v1/reports/vehicle-service/job-feedback?'.http_build_query([
            'tenant_id' => $context['tenant_id'],
            'is_customer_visible' => 'internal_only',
        ]));
        $resInternal->assertOk();
        $this->assertCount(1, $resInternal->json('data'));
        $this->assertFalse($resInternal->json('data.0.is_customer_visible'));
    }

    public function test_service_job_feedback_report_filters_by_date_and_search(): void
    {
        $context = $this->context('FBR5');
        $this->authorize($context);

        $jobType = $this->ensureJobType($context);
        $vehicle = $this->vehicle($context['tenant_id'], $context['organization_unit_id'], $context['customer_id'], 'FB-V5');
        $job1 = $this->createJob($context, $vehicle, '2026-06-01', (int) $jobType->getKey());
        $job2 = $this->createJob($context, $vehicle, '2026-06-15', (int) $jobType->getKey());

        $this->createFeedback($context, $job1, VehicleServiceFeedbackType::Review, 5, 'Engine check', 'Quiet running engine', true, 'Inspector John');
        $this->createFeedback($context, $job2, VehicleServiceFeedbackType::Review, 4, 'Brakes done', 'Firm pedal feel', true, 'Driver Sam');

        // Date filter
        $resDate = $this->reportGetJson($context, '/api/v1/reports/vehicle-service/job-feedback?'.http_build_query([
            'tenant_id' => $context['tenant_id'],
            'date_from' => '2026-06-10',
            'date_to' => '2026-06-20',
        ]));
        $resDate->assertOk();
        $this->assertCount(1, $resDate->json('data'));
        $this->assertSame('Brakes done', $resDate->json('data.0.title'));

        // Search filter matching comment keyword
        $resSearch = $this->reportGetJson($context, '/api/v1/reports/vehicle-service/job-feedback?'.http_build_query([
            'tenant_id' => $context['tenant_id'],
            'search' => 'Quiet running',
        ]));
        $resSearch->assertOk();
        $this->assertCount(1, $resSearch->json('data'));
        $this->assertSame('Engine check', $resSearch->json('data.0.title'));
    }

    public function test_service_job_feedback_report_enforces_tenant_isolation(): void
    {
        $contextA = $this->context('FBA');
        $contextB = $this->context('FBB');
        $this->authorize($contextA);
        $this->authorize($contextB);

        $jobTypeA = $this->ensureJobType($contextA);
        $vehicleA = $this->vehicle($contextA['tenant_id'], $contextA['organization_unit_id'], $contextA['customer_id'], 'FB-VA');
        $jobA = $this->createJob($contextA, $vehicleA, '2026-06-01', (int) $jobTypeA->getKey());
        $this->createFeedback($contextA, $jobA, VehicleServiceFeedbackType::Review, 5, 'Tenant A Review', 'Great', true, 'User A');

        $jobTypeB = $this->ensureJobType($contextB);
        $vehicleB = $this->vehicle($contextB['tenant_id'], $contextB['organization_unit_id'], $contextB['customer_id'], 'FB-VB');
        $jobB = $this->createJob($contextB, $vehicleB, '2026-06-01', (int) $jobTypeB->getKey());
        $this->createFeedback($contextB, $jobB, VehicleServiceFeedbackType::Review, 1, 'Tenant B Secret', 'Bad', true, 'User B');

        // Switch back to context A for testing query isolation
        $this->actingAs($this->withTenantExecutionContext(
            $contextA['tenant_id'],
            fn (): UserModel => UserModel::query()->findOrFail($contextA['user_id']),
        ), UserGuard::TENANT_API);

        $response = $this->reportGetJson($contextA, '/api/v1/reports/vehicle-service/job-feedback?'.http_build_query([
            'tenant_id' => $contextA['tenant_id'],
        ]));

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Tenant A Review', $response->json('data.0.title'));
    }

    public function test_service_job_feedback_report_export_csv_and_pdf(): void
    {
        $context = $this->context('FBEXP');
        $this->authorize($context);

        $jobType = $this->ensureJobType($context);
        $vehicle = $this->vehicle($context['tenant_id'], $context['organization_unit_id'], $context['customer_id'], 'FB-VEXP');
        $job = $this->createJob($context, $vehicle, '2026-06-01', (int) $jobType->getKey());
        $this->createFeedback($context, $job, VehicleServiceFeedbackType::Review, 5, 'Export Title', 'Export comment description', true, 'Tester');

        // Test CSV export
        $csvRes = $this->reportGet($context, '/api/v1/reports/vehicle-service/job-feedback/export/csv?'.http_build_query([
            'tenant_id' => $context['tenant_id'],
        ]));
        $csvRes->assertOk();
        $this->assertStringContainsString('text/csv', (string) $csvRes->headers->get('content-type'));
        $this->assertStringContainsString('Export Title', (string) $csvRes->getContent());

        // Test PDF export
        Pdf::fake();
        $pdfRes = $this->reportGet($context, '/api/v1/reports/vehicle-service/job-feedback/export/pdf?'.http_build_query([
            'tenant_id' => $context['tenant_id'],
        ]));
        $pdfRes->assertOk();
        Pdf::assertRespondedWithPdf(
            fn (PdfBuilder $pdf): bool => $pdf->viewData['mode'] === 'pdf'
        );
    }

    private function createFeedback(
        array $context,
        VehicleServiceJob $job,
        VehicleServiceFeedbackType $type,
        ?int $rating,
        ?string $title,
        string $comment,
        bool $isCustomerVisible,
        string $authorName,
    ): void {
        $actorId = (int) ($context['user_id'] ?? 1);
        $this->withTenantExecutionContext((int) $job->tenant_id, function () use ($job, $type, $rating, $title, $comment, $isCustomerVisible, $authorName, $actorId): void {
            app(VehicleServiceJobFeedbackService::class)->create(
                $job,
                new VehicleServiceJobFeedbackData(
                    feedbackType: $type,
                    rating: $rating,
                    title: $title,
                    comment: $comment,
                    isCustomerVisible: $isCustomerVisible,
                    authorName: $authorName,
                ),
                $actorId
            );
        });
    }

    private function ensureJobType(array $context): VehicleServiceJobType
    {
        return $this->withTenantExecutionContext((int) $context['tenant_id'], function () use ($context): VehicleServiceJobType {
            app(VehicleServiceJobTypeService::class)->ensureDefaults($context['tenant_id'], $context['organization_unit_id']);

            return VehicleServiceJobType::query()->where('tenant_id', $context['tenant_id'])->where('code', 'full_service')->firstOrFail();
        });
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function createJob(
        array $context,
        int $vehicleId,
        string $jobDate,
        int $jobTypeId,
    ): VehicleServiceJob {
        return $this->withTenantExecutionContext((int) $context['tenant_id'], function () use ($context, $vehicleId, $jobDate, $jobTypeId): VehicleServiceJob {
            return app(VehicleServiceJobService::class)->create(new VehicleServiceJobData(
                tenantId: (int) $context['tenant_id'],
                jobDate: $jobDate,
                customerId: (int) $context['customer_id'],
                vehicleId: $vehicleId,
                jobTypeId: $jobTypeId,
                organizationUnitId: $context['organization_unit_id'] ?? null,
                odometerReading: '15000.000000',
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

    /**
     * @param  array<string, mixed>  $context
     */
    private function reportGetJson(array $context, string $uri): TestResponse
    {
        return $this->withTenantRequestContext(
            (int) $context['tenant_id'],
            (int) $context['user_id'],
            fn (): TestResponse => $this->getJson($uri),
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function reportGet(array $context, string $uri): TestResponse
    {
        return $this->withTenantRequestContext(
            (int) $context['tenant_id'],
            (int) $context['user_id'],
            fn (): TestResponse => $this->get($uri),
        );
    }
}
