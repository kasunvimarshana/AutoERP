<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleService\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Contracts\TenantAccessProvisionerInterface;
use Modules\Customer\Models\Customer;
use Modules\Hr\Models\HrEmployee;
use Modules\User\Models\UserModel;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleService\DTOs\VehicleServiceJobData;
use Modules\VehicleService\Enums\VehicleServiceFeedbackType;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceJobFeedback;
use Modules\VehicleService\Models\VehicleServiceJobType;
use Modules\VehicleService\Services\VehicleServiceJobService;
use Modules\VehicleService\Services\VehicleServiceJobTypeService;
use Tests\Support\OrganizationUnitFixture;
use Tests\Support\TenantUserFixture;
use Tests\TestCase;

final class VehicleServiceJobFeedbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
    }

    public function test_can_list_feedbacks_and_summary_with_tenant_isolation(): void
    {
        $context = $this->createContext();
        $job = $this->createJob($context);

        $this->withTenantExecutionContext($context['tenant_id'], function () use ($context, $job): void {
            VehicleServiceJobFeedback::query()->create([
                'tenant_id' => $context['tenant_id'],
                'organization_unit_id' => $context['organization_unit_id'],
                'vehicle_service_job_id' => $job->id,
                'user_id' => $context['user_id'],
                'author_name' => 'Alice Customer',
                'feedback_type' => VehicleServiceFeedbackType::Review,
                'rating' => 5,
                'title' => 'Top notch work',
                'comment' => 'They finished the car on time and cleanly.',
                'is_customer_visible' => true,
                'row_version' => 1,
            ]);

            VehicleServiceJobFeedback::query()->create([
                'tenant_id' => $context['tenant_id'],
                'organization_unit_id' => $context['organization_unit_id'],
                'vehicle_service_job_id' => $job->id,
                'user_id' => $context['user_id'],
                'author_name' => 'Bob Advisor',
                'feedback_type' => VehicleServiceFeedbackType::InternalNote,
                'rating' => null,
                'title' => 'Customer requested synthetic oil',
                'comment' => 'Applied synthetic 5W-30 as requested.',
                'is_customer_visible' => false,
                'row_version' => 1,
            ]);
        });

        $response = $this->tenantGetJson(
            $context['tenant_id'],
            "/api/v1/vehicle-service/jobs/{$job->id}/feedbacks?tenant_id={$context['tenant_id']}&organization_unit_id={$context['organization_unit_id']}",
        );

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id', 'row_version', 'tenant_id', 'vehicle_service_job_id', 'feedback_type',
                    'feedback_type_label', 'rating', 'title', 'comment', 'is_customer_visible',
                    'author_name', 'created_at',
                ],
            ],
            'meta' => [
                'summary' => [
                    'average_rating', 'total_count', 'ratings_count', 'reviews_count',
                    'notes_count', 'rating_breakdown',
                ],
            ],
        ]);

        $data = $response->json('data');
        $this->assertCount(2, $data);
        $summary = $response->json('meta.summary');
        $this->assertSame(2, $summary['total_count']);
        $this->assertSame(1, $summary['reviews_count']);
        $this->assertSame(1, $summary['notes_count']);
        $this->assertEquals(5.0, $summary['average_rating']);
        $this->assertSame(1, $summary['rating_breakdown'][5]);

        // Verify tenant isolation with another tenant
        $otherContext = $this->createContext('other');
        $otherResponse = $this->tenantGetJson(
            $otherContext['tenant_id'],
            "/api/v1/vehicle-service/jobs/{$job->id}/feedbacks?tenant_id={$otherContext['tenant_id']}&organization_unit_id={$otherContext['organization_unit_id']}",
        );
        $otherResponse->assertNotFound();
    }

    public function test_can_create_customer_review_with_star_rating(): void
    {
        $context = $this->createContext();
        $job = $this->createJob($context);

        $response = $this->tenantPostJson(
            $context['tenant_id'],
            "/api/v1/vehicle-service/jobs/{$job->id}/feedbacks",
            [
                'tenant_id' => $context['tenant_id'],
                'organization_unit_id' => $context['organization_unit_id'],
                'feedback_type' => 'review',
                'rating' => 4,
                'title' => 'Quick and smooth',
                'comment' => 'Everything went smoothly. Brake pads replaced properly.',
                'author_name' => 'John Customer',
                'is_customer_visible' => true,
            ],
        );

        $response->assertCreated();
        $response->assertJsonPath('data.rating', 4);
        $response->assertJsonPath('data.feedback_type', 'review');
        $response->assertJsonPath('data.feedback_type_label', 'Customer Review');
        $response->assertJsonPath('data.author_name', 'John Customer');
        $response->assertJsonPath('data.is_customer_visible', true);
        $response->assertJsonPath('data.row_version', 1);

        $this->assertDatabaseHas('vehicle_service_job_feedbacks', [
            'tenant_id' => $context['tenant_id'],
            'vehicle_service_job_id' => $job->id,
            'feedback_type' => 'review',
            'rating' => 4,
            'title' => 'Quick and smooth',
            'author_name' => 'John Customer',
            'is_customer_visible' => 1,
        ]);
    }

    public function test_can_create_internal_note_without_rating(): void
    {
        $context = $this->createContext();
        $job = $this->createJob($context);

        $response = $this->tenantPostJson(
            $context['tenant_id'],
            "/api/v1/vehicle-service/jobs/{$job->id}/feedbacks",
            [
                'tenant_id' => $context['tenant_id'],
                'organization_unit_id' => $context['organization_unit_id'],
                'feedback_type' => 'internal_note',
                'comment' => 'Check front suspension bushings during next inspection.',
                'is_customer_visible' => false,
            ],
        );

        $response->assertCreated();
        $response->assertJsonPath('data.rating', null);
        $response->assertJsonPath('data.feedback_type', 'internal_note');
        $response->assertJsonPath('data.is_customer_visible', false);

        $this->assertDatabaseHas('vehicle_service_job_feedbacks', [
            'tenant_id' => $context['tenant_id'],
            'vehicle_service_job_id' => $job->id,
            'feedback_type' => 'internal_note',
            'rating' => null,
            'is_customer_visible' => 0,
        ]);
    }

    public function test_rating_validation_rules(): void
    {
        $context = $this->createContext();
        $job = $this->createJob($context);

        // Rating > 5
        $response = $this->tenantPostJson(
            $context['tenant_id'],
            "/api/v1/vehicle-service/jobs/{$job->id}/feedbacks",
            [
                'tenant_id' => $context['tenant_id'],
                'organization_unit_id' => $context['organization_unit_id'],
                'feedback_type' => 'review',
                'rating' => 6,
                'comment' => 'Invalid star rating test',
            ],
        );
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['rating']);

        // Rating < 1
        $response = $this->tenantPostJson(
            $context['tenant_id'],
            "/api/v1/vehicle-service/jobs/{$job->id}/feedbacks",
            [
                'tenant_id' => $context['tenant_id'],
                'organization_unit_id' => $context['organization_unit_id'],
                'feedback_type' => 'review',
                'rating' => 0,
                'comment' => 'Invalid star rating test',
            ],
        );
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['rating']);

        // Invalid feedback_type
        $response = $this->tenantPostJson(
            $context['tenant_id'],
            "/api/v1/vehicle-service/jobs/{$job->id}/feedbacks",
            [
                'tenant_id' => $context['tenant_id'],
                'organization_unit_id' => $context['organization_unit_id'],
                'feedback_type' => 'unsupported_type',
                'comment' => 'Some feedback text',
            ],
        );
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['feedback_type']);
    }

    public function test_can_create_feedback_with_all_optional_fields_omitted(): void
    {
        $context = $this->createContext();
        $job = $this->createJob($context);

        $response = $this->tenantPostJson(
            $context['tenant_id'],
            "/api/v1/vehicle-service/jobs/{$job->id}/feedbacks",
            [
                'tenant_id' => $context['tenant_id'],
                'organization_unit_id' => $context['organization_unit_id'],
            ],
        );

        $response->assertCreated();
        $response->assertJsonPath('data.feedback_type', 'review');
        $response->assertJsonPath('data.rating', null);
        $response->assertJsonPath('data.title', null);
        $response->assertJsonPath('data.comment', null);
        $response->assertJsonPath('data.is_customer_visible', true);

        $this->assertDatabaseHas('vehicle_service_job_feedbacks', [
            'tenant_id' => $context['tenant_id'],
            'vehicle_service_job_id' => $job->id,
            'feedback_type' => 'review',
            'rating' => null,
            'title' => null,
            'comment' => null,
        ]);
    }

    public function test_can_update_feedback_with_all_optional_fields(): void
    {
        $context = $this->createContext();
        $job = $this->createJob($context);

        /** @var VehicleServiceJobFeedback $feedback */
        $feedback = $this->withTenantExecutionContext($context['tenant_id'], function () use ($context, $job) {
            return VehicleServiceJobFeedback::query()->create([
                'tenant_id' => $context['tenant_id'],
                'organization_unit_id' => $context['organization_unit_id'],
                'vehicle_service_job_id' => $job->id,
                'user_id' => $context['user_id'],
                'feedback_type' => VehicleServiceFeedbackType::Review,
                'rating' => 4,
                'title' => 'Initial Title',
                'comment' => 'Initial comment',
                'is_customer_visible' => true,
                'row_version' => 1,
            ]);
        });

        // Update without expected_version, without feedback_type, without comment
        $updateResponse = $this->tenantPutJson(
            $context['tenant_id'],
            "/api/v1/vehicle-service/jobs/{$job->id}/feedbacks/{$feedback->id}",
            [
                'tenant_id' => $context['tenant_id'],
                'organization_unit_id' => $context['organization_unit_id'],
                'title' => 'Updated title only',
            ],
        );

        $updateResponse->assertOk();
        $updateResponse->assertJsonPath('data.title', 'Updated title only');
        $updateResponse->assertJsonPath('data.feedback_type', 'review');
        $updateResponse->assertJsonPath('data.row_version', 2);
    }

    public function test_search_matches_submitter_user_name_when_author_name_is_null(): void
    {
        $context = $this->createContext();
        $job = $this->createJob($context);

        $this->withTenantExecutionContext($context['tenant_id'], function () use ($context, $job): void {
            VehicleServiceJobFeedback::query()->create([
                'tenant_id' => $context['tenant_id'],
                'organization_unit_id' => $context['organization_unit_id'],
                'vehicle_service_job_id' => $job->id,
                'user_id' => $context['user_id'],
                'author_name' => null,
                'feedback_type' => VehicleServiceFeedbackType::Review,
                'rating' => 5,
                'title' => 'Great oil change',
                'comment' => 'Completed smoothly',
                'row_version' => 1,
            ]);
        });

        // Search by user's first name 'Service' or 'Admin'
        $response = $this->tenantGetJson(
            $context['tenant_id'],
            "/api/v1/vehicle-service/jobs/{$job->id}/feedbacks?tenant_id={$context['tenant_id']}&organization_unit_id={$context['organization_unit_id']}&search=Service Admin",
        );
        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Great oil change', $response->json('data.0.title'));
    }

    public function test_can_update_feedback_with_optimistic_concurrency(): void
    {
        $context = $this->createContext();
        $job = $this->createJob($context);

        /** @var VehicleServiceJobFeedback $feedback */
        $feedback = $this->withTenantExecutionContext($context['tenant_id'], function () use ($context, $job) {
            return VehicleServiceJobFeedback::query()->create([
                'tenant_id' => $context['tenant_id'],
                'organization_unit_id' => $context['organization_unit_id'],
                'vehicle_service_job_id' => $job->id,
                'user_id' => $context['user_id'],
                'feedback_type' => VehicleServiceFeedbackType::Review,
                'rating' => 3,
                'title' => 'Average service',
                'comment' => 'Took a bit longer than expected.',
                'is_customer_visible' => true,
                'row_version' => 1,
            ]);
        });

        // Update with matching expected_version = 1
        $updateResponse = $this->tenantPutJson(
            $context['tenant_id'],
            "/api/v1/vehicle-service/jobs/{$job->id}/feedbacks/{$feedback->id}",
            [
                'tenant_id' => $context['tenant_id'],
                'organization_unit_id' => $context['organization_unit_id'],
                'expected_version' => 1,
                'feedback_type' => 'review',
                'rating' => 5,
                'title' => 'Resolved - great service after follow up',
                'comment' => 'Manager clarified the extra time was for full inspection. Very pleased!',
                'is_customer_visible' => true,
            ],
        );

        $updateResponse->assertOk();
        $updateResponse->assertJsonPath('data.rating', 5);
        $updateResponse->assertJsonPath('data.title', 'Resolved - great service after follow up');
        $updateResponse->assertJsonPath('data.row_version', 2);

        // Attempting another update with stale expected_version = 1 fails with 422
        $staleResponse = $this->tenantPutJson(
            $context['tenant_id'],
            "/api/v1/vehicle-service/jobs/{$job->id}/feedbacks/{$feedback->id}",
            [
                'tenant_id' => $context['tenant_id'],
                'organization_unit_id' => $context['organization_unit_id'],
                'expected_version' => 1,
                'feedback_type' => 'review',
                'rating' => 2,
                'comment' => 'Should fail due to stale expected_version.',
            ],
        );

        $staleResponse->assertUnprocessable();
        $staleResponse->assertJsonValidationErrors(['expected_version']);
    }

    public function test_can_delete_feedback(): void
    {
        $context = $this->createContext();
        $job = $this->createJob($context);

        /** @var VehicleServiceJobFeedback $feedback */
        $feedback = $this->withTenantExecutionContext($context['tenant_id'], function () use ($context, $job) {
            return VehicleServiceJobFeedback::query()->create([
                'tenant_id' => $context['tenant_id'],
                'organization_unit_id' => $context['organization_unit_id'],
                'vehicle_service_job_id' => $job->id,
                'user_id' => $context['user_id'],
                'feedback_type' => VehicleServiceFeedbackType::InternalNote,
                'comment' => 'Note to be removed.',
                'row_version' => 1,
            ]);
        });

        $deleteResponse = $this->tenantDeleteJson(
            $context['tenant_id'],
            "/api/v1/vehicle-service/jobs/{$job->id}/feedbacks/{$feedback->id}",
            [
                'tenant_id' => $context['tenant_id'],
                'organization_unit_id' => $context['organization_unit_id'],
                'expected_version' => 1,
            ],
        );

        $deleteResponse->assertNoContent();

        $this->assertSoftDeleted('vehicle_service_job_feedbacks', [
            'id' => $feedback->id,
            'tenant_id' => $context['tenant_id'],
        ]);
    }

    public function test_summary_endpoint_computes_accurate_rating_breakdown(): void
    {
        $context = $this->createContext();
        $job = $this->createJob($context);

        $this->withTenantExecutionContext($context['tenant_id'], function () use ($context, $job): void {
            // Two 5-star reviews, one 2-star review, one note
            VehicleServiceJobFeedback::query()->create([
                'tenant_id' => $context['tenant_id'],
                'organization_unit_id' => $context['organization_unit_id'],
                'vehicle_service_job_id' => $job->id,
                'user_id' => $context['user_id'],
                'feedback_type' => VehicleServiceFeedbackType::Review,
                'rating' => 5,
                'comment' => 'Excellent job 1',
                'row_version' => 1,
            ]);
            VehicleServiceJobFeedback::query()->create([
                'tenant_id' => $context['tenant_id'],
                'organization_unit_id' => $context['organization_unit_id'],
                'vehicle_service_job_id' => $job->id,
                'user_id' => $context['user_id'],
                'feedback_type' => VehicleServiceFeedbackType::Review,
                'rating' => 5,
                'comment' => 'Excellent job 2',
                'row_version' => 1,
            ]);
            VehicleServiceJobFeedback::query()->create([
                'tenant_id' => $context['tenant_id'],
                'organization_unit_id' => $context['organization_unit_id'],
                'vehicle_service_job_id' => $job->id,
                'user_id' => $context['user_id'],
                'feedback_type' => VehicleServiceFeedbackType::Review,
                'rating' => 2,
                'comment' => 'Took too long',
                'row_version' => 1,
            ]);
            VehicleServiceJobFeedback::query()->create([
                'tenant_id' => $context['tenant_id'],
                'organization_unit_id' => $context['organization_unit_id'],
                'vehicle_service_job_id' => $job->id,
                'user_id' => $context['user_id'],
                'feedback_type' => VehicleServiceFeedbackType::InternalNote,
                'rating' => null,
                'comment' => 'Staff note without rating',
                'row_version' => 1,
            ]);
        });

        $response = $this->tenantGetJson(
            $context['tenant_id'],
            "/api/v1/vehicle-service/jobs/{$job->id}/feedbacks/summary?tenant_id={$context['tenant_id']}&organization_unit_id={$context['organization_unit_id']}",
        );

        $response->assertOk();
        // (5 + 5 + 2) / 3 = 12 / 3 = 4.0
        $response->assertJsonPath('data.average_rating', 4);
        $response->assertJsonPath('data.total_count', 4);
        $response->assertJsonPath('data.ratings_count', 3);
        $response->assertJsonPath('data.reviews_count', 3);
        $response->assertJsonPath('data.notes_count', 1);
        $response->assertJsonPath('data.rating_breakdown.5', 2);
        $response->assertJsonPath('data.rating_breakdown.2', 1);
        $response->assertJsonPath('data.rating_breakdown.4', 0);
    }

    public function test_filtering_feedbacks_by_type_and_rating(): void
    {
        $context = $this->createContext();
        $job = $this->createJob($context);

        $this->withTenantExecutionContext($context['tenant_id'], function () use ($context, $job): void {
            VehicleServiceJobFeedback::query()->create([
                'tenant_id' => $context['tenant_id'],
                'organization_unit_id' => $context['organization_unit_id'],
                'vehicle_service_job_id' => $job->id,
                'feedback_type' => VehicleServiceFeedbackType::Review,
                'rating' => 5,
                'title' => 'Super fast',
                'comment' => 'Filter search term Alpha',
                'row_version' => 1,
            ]);
            VehicleServiceJobFeedback::query()->create([
                'tenant_id' => $context['tenant_id'],
                'organization_unit_id' => $context['organization_unit_id'],
                'vehicle_service_job_id' => $job->id,
                'feedback_type' => VehicleServiceFeedbackType::InternalNote,
                'rating' => null,
                'title' => 'Internal info',
                'comment' => 'Filter search term Beta',
                'row_version' => 1,
            ]);
        });

        // Filter by feedback_type=internal_note
        $response = $this->tenantGetJson(
            $context['tenant_id'],
            "/api/v1/vehicle-service/jobs/{$job->id}/feedbacks?tenant_id={$context['tenant_id']}&organization_unit_id={$context['organization_unit_id']}&feedback_type=internal_note",
        );
        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('internal_note', $response->json('data.0.feedback_type'));

        // Filter by search=Alpha
        $searchResponse = $this->tenantGetJson(
            $context['tenant_id'],
            "/api/v1/vehicle-service/jobs/{$job->id}/feedbacks?tenant_id={$context['tenant_id']}&organization_unit_id={$context['organization_unit_id']}&search=Alpha",
        );
        $searchResponse->assertOk();
        $this->assertCount(1, $searchResponse->json('data'));
        $this->assertSame('Super fast', $searchResponse->json('data.0.title'));
    }

    /**
     * @return array{
     *     tenant_id: int,
     *     organization_unit_id: int,
     *     user_id: int,
     *     customer_id: int,
     *     vehicle_id: int,
     *     supervisor_id: int,
     *     customer: Customer,
     *     vehicle: Vehicle,
     *     supervisor: HrEmployee
     * }
     */
    private function createContext(string $customSuffix = ''): array
    {
        $now = now();
        $suffix = ($customSuffix !== '' ? $customSuffix.'-' : '').Str::lower(Str::random(6));

        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-'.$suffix,
            'name' => 'Tenant '.$suffix,
            'slug' => 'tenant-'.$suffix,
            'status' => 'active',
            'row_version' => 1,
            'status_reason' => 'Feedback test setup.',
            'status_changed_at' => $now,
            'activated_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $organizationUnitId = (int) OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => 'Main Workshop',
            'code' => 'WS-'.$suffix,
            'path' => '/ws-'.$suffix,
            'is_active' => true,
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $userId = TenantUserFixture::create([
            'tenant_id' => $tenantId,
            'first_name' => 'Service',
            'last_name' => 'Admin',
            'email' => 'admin-'.$suffix.'@example.test',
            'status' => 'active',
        ]);

        return $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $organizationUnitId, $userId, $suffix, $now): array {
            $customerId = (int) DB::table('customers')->insertGetId([
                'tenant_id' => $tenantId,
                'customer_number' => 'CUST-'.$suffix,
                'code' => 'C-'.$suffix,
                'name' => 'Customer '.$suffix,
                'display_name' => 'Customer '.$suffix,
                'customer_type' => 'individual',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $customer = Customer::query()->findOrFail($customerId);

            $vehicleId = (int) DB::table('vehicles')->insertGetId([
                'tenant_id' => $tenantId,
                'vehicle_number' => 'V-'.$suffix,
                'registration_number' => 'REG-'.$suffix,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('vehicle_ownerships')->insert([
                'row_version' => 1,
                'tenant_id' => $tenantId,
                'organization_unit_id' => null,
                'vehicle_id' => $vehicleId,
                'owner_type' => 'customer',
                'owner_id' => $customerId,
                'owner_key' => 'customer:'.$customerId,
                'owner_code_snapshot' => 'C-'.$suffix,
                'owner_name_snapshot' => 'Customer '.$suffix,
                'ownership_type' => 'customer_owned',
                'started_at' => $now,
                'is_current' => true,
                'current_guard' => 1,
                'active_guard' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $vehicle = Vehicle::query()->findOrFail($vehicleId);

            $designationId = (int) DB::table('hr_designations')->insertGetId([
                'tenant_id' => $tenantId,
                'code' => 'SUPERVISOR',
                'name' => 'Workshop Supervisor',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $employeeId = (int) DB::table('hr_employees')->insertGetId([
                'tenant_id' => $tenantId,
                'employee_number' => 'EMP-'.$suffix,
                'code' => 'EMP-'.$suffix,
                'first_name' => 'Supervisor',
                'last_name' => 'One',
                'display_name' => 'Supervisor One',
                'designation_id' => $designationId,
                'status' => 'active',
                'availability_status' => 'available',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $supervisor = HrEmployee::query()->findOrFail($employeeId);

            // Provision tenant permissions
            app(TenantAccessProvisionerInterface::class)->provision($tenantId);

            // Assign super admin role to user
            $roleId = DB::table('roles')->where('tenant_id', $tenantId)->value('id');
            if ($roleId !== null) {
                DB::table('user_roles')->insert([
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'role_id' => $roleId,
                    'row_version' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $user = UserModel::query()->findOrFail($userId);
            $this->actingAs($user, (string) config('module-auth.protected_route_guard', 'auth-api'));

            return [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'user_id' => $userId,
                'customer_id' => $customerId,
                'vehicle_id' => $vehicleId,
                'supervisor_id' => $employeeId,
                'customer' => $customer,
                'vehicle' => $vehicle,
                'supervisor' => $supervisor,
            ];
        });
    }

    /**
     * @param  array{tenant_id: int, organization_unit_id: int, customer_id: int, vehicle_id: int, supervisor_id: int}  $context
     */
    private function createJob(array $context): VehicleServiceJob
    {
        return $this->withTenantExecutionContext($context['tenant_id'], function () use ($context): VehicleServiceJob {
            $typeService = app(VehicleServiceJobTypeService::class);
            $typeService->ensureDefaults($context['tenant_id'], $context['organization_unit_id']);
            $type = VehicleServiceJobType::query()
                ->where('tenant_id', $context['tenant_id'])
                ->where('code', 'full_service')
                ->firstOrFail();

            $jobService = app(VehicleServiceJobService::class);

            return $jobService->create(new VehicleServiceJobData(
                tenantId: $context['tenant_id'],
                organizationUnitId: $context['organization_unit_id'],
                jobDate: Carbon::now()->toDateString(),
                jobTypeId: $type->id,
                type: $type->code,
                customerId: $context['customer_id'],
                billToCustomerId: $context['customer_id'],
                vehicleId: $context['vehicle_id'],
                odometerReading: '50000',
                supervisorEmployeeId: $context['supervisor_id'],
                expectedDeliveryDate: Carbon::now()->addDays(2)->toDateString(),
            ));
        });
    }
}
