<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\PresenceVerifierInterface;
use Laravel\Passport\Passport;
use Modules\HR\Application\Contracts\ApproveLeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\ApprovePayrollRunServiceInterface;
use Modules\HR\Application\Contracts\AssignShiftServiceInterface;
use Modules\HR\Application\Contracts\CancelLeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\CreateBiometricDeviceServiceInterface;
use Modules\HR\Application\Contracts\CreateLeavePolicyServiceInterface;
use Modules\HR\Application\Contracts\CreateLeaveTypeServiceInterface;
use Modules\HR\Application\Contracts\CreatePayrollRunServiceInterface;
use Modules\HR\Application\Contracts\CreatePerformanceReviewServiceInterface;
use Modules\HR\Application\Contracts\CreatePerformanceCycleServiceInterface;
use Modules\HR\Application\Contracts\CreateShiftServiceInterface;
use Modules\HR\Application\Contracts\DeleteBiometricDeviceServiceInterface;
use Modules\HR\Application\Contracts\DeleteLeaveTypeServiceInterface;
use Modules\HR\Application\Contracts\DeleteShiftServiceInterface;
use Modules\HR\Application\Contracts\FindAttendanceLogServiceInterface;
use Modules\HR\Application\Contracts\FindAttendanceRecordServiceInterface;
use Modules\HR\Application\Contracts\FindBiometricDeviceServiceInterface;
use Modules\HR\Application\Contracts\FindLeaveBalanceServiceInterface;
use Modules\HR\Application\Contracts\FindLeavePolicyServiceInterface;
use Modules\HR\Application\Contracts\FindLeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\FindLeaveTypeServiceInterface;
use Modules\HR\Application\Contracts\FindPayrollItemServiceInterface;
use Modules\HR\Application\Contracts\FindPayrollRunServiceInterface;
use Modules\HR\Application\Contracts\FindPayslipServiceInterface;
use Modules\HR\Application\Contracts\FindPerformanceCycleServiceInterface;
use Modules\HR\Application\Contracts\FindPerformanceReviewServiceInterface;
use Modules\HR\Application\Contracts\FindShiftServiceInterface;
use Modules\HR\Application\Contracts\ProcessAttendanceServiceInterface;
use Modules\HR\Application\Contracts\ProcessPayrollRunServiceInterface;
use Modules\HR\Application\Contracts\RejectLeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\SubmitPerformanceReviewServiceInterface;
use Modules\HR\Application\Contracts\SubmitLeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\SyncBiometricDeviceServiceInterface;
use Modules\HR\Application\Contracts\UpdateAttendanceRecordServiceInterface;
use Modules\HR\Application\Contracts\UpdateBiometricDeviceServiceInterface;
use Modules\HR\Application\Contracts\UpdateLeaveTypeServiceInterface;
use Modules\HR\Application\Contracts\UpdateLeavePolicyServiceInterface;
use Modules\HR\Application\Contracts\UpdatePayrollItemServiceInterface;
use Modules\HR\Application\Contracts\UpdatePerformanceCycleServiceInterface;
use Modules\HR\Application\Contracts\UpdatePerformanceReviewServiceInterface;
use Modules\HR\Application\Contracts\UpdateShiftServiceInterface;
use Modules\HR\Application\Contracts\CreateAttendanceLogServiceInterface;
use Modules\HR\Application\Contracts\CreatePayrollItemServiceInterface;
use Modules\HR\Application\Contracts\StoreEmployeeDocumentServiceInterface;
use Modules\HR\Application\Contracts\FindEmployeeDocumentServiceInterface;
use Modules\HR\Application\Contracts\DeleteEmployeeDocumentServiceInterface;
use Modules\HR\Domain\Entities\AttendanceLog;
use Modules\HR\Domain\Entities\AttendanceRecord;
use Modules\HR\Domain\ValueObjects\AttendanceStatus;
use Modules\HR\Domain\Entities\BiometricDevice;
use Modules\HR\Domain\Entities\LeaveRequest;
use Modules\HR\Domain\Entities\LeaveType;
use Modules\HR\Domain\Entities\PayrollRun;
use Modules\HR\Domain\Entities\PerformanceReview;
use Modules\HR\Domain\Entities\Shift;
use Modules\HR\Domain\Entities\ShiftAssignment;
use Modules\HR\Domain\Entities\LeaveBalance;
use Modules\HR\Domain\Entities\LeavePolicy;
use Modules\HR\Domain\Entities\PayrollItem;
use Modules\HR\Domain\Entities\Payslip;
use Modules\HR\Domain\Entities\PerformanceCycle;
use Modules\HR\Domain\Entities\EmployeeDocument;
use Modules\HR\Domain\ValueObjects\BiometricDeviceStatus;
use Modules\HR\Domain\ValueObjects\LeaveRequestStatus;
use Modules\HR\Domain\ValueObjects\PerformanceRating;
use Modules\HR\Domain\ValueObjects\PayrollRunStatus;
use Modules\HR\Domain\ValueObjects\ShiftType;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class HREndpointsAuthenticatedTest extends TestCase
{
    private static bool $routesCleared = false;

    /** @var FindShiftServiceInterface&MockObject */
    private FindShiftServiceInterface $findShiftService;

    /** @var CreateShiftServiceInterface&MockObject */
    private CreateShiftServiceInterface $createShiftService;

    /** @var UpdateShiftServiceInterface&MockObject */
    private UpdateShiftServiceInterface $updateShiftService;

    /** @var DeleteShiftServiceInterface&MockObject */
    private DeleteShiftServiceInterface $deleteShiftService;

    /** @var FindLeaveTypeServiceInterface&MockObject */
    private FindLeaveTypeServiceInterface $findLeaveTypeService;

    /** @var CreateLeaveTypeServiceInterface&MockObject */
    private CreateLeaveTypeServiceInterface $createLeaveTypeService;

    /** @var UpdateLeaveTypeServiceInterface&MockObject */
    private UpdateLeaveTypeServiceInterface $updateLeaveTypeService;

    /** @var DeleteLeaveTypeServiceInterface&MockObject */
    private DeleteLeaveTypeServiceInterface $deleteLeaveTypeService;

    /** @var FindLeaveRequestServiceInterface&MockObject */
    private FindLeaveRequestServiceInterface $findLeaveRequestService;

    /** @var AssignShiftServiceInterface&MockObject */
    private AssignShiftServiceInterface $assignShiftService;

    /** @var SubmitLeaveRequestServiceInterface&MockObject */
    private SubmitLeaveRequestServiceInterface $submitLeaveRequestService;

    /** @var ApproveLeaveRequestServiceInterface&MockObject */
    private ApproveLeaveRequestServiceInterface $approveLeaveRequestService;

    /** @var RejectLeaveRequestServiceInterface&MockObject */
    private RejectLeaveRequestServiceInterface $rejectLeaveRequestService;

    /** @var CancelLeaveRequestServiceInterface&MockObject */
    private CancelLeaveRequestServiceInterface $cancelLeaveRequestService;

    /** @var FindPayrollRunServiceInterface&MockObject */
    private FindPayrollRunServiceInterface $findPayrollRunService;

    /** @var ApprovePayrollRunServiceInterface&MockObject */
    private ApprovePayrollRunServiceInterface $approvePayrollRunService;

    /** @var ProcessPayrollRunServiceInterface&MockObject */
    private ProcessPayrollRunServiceInterface $processPayrollRunService;

    /** @var FindBiometricDeviceServiceInterface&MockObject */
    private FindBiometricDeviceServiceInterface $findBiometricDeviceService;

    /** @var SyncBiometricDeviceServiceInterface&MockObject */
    private SyncBiometricDeviceServiceInterface $syncBiometricDeviceService;

    /** @var CreateBiometricDeviceServiceInterface&MockObject */
    private CreateBiometricDeviceServiceInterface $createBiometricDeviceService;

    /** @var UpdateBiometricDeviceServiceInterface&MockObject */
    private UpdateBiometricDeviceServiceInterface $updateBiometricDeviceService;

    /** @var DeleteBiometricDeviceServiceInterface&MockObject */
    private DeleteBiometricDeviceServiceInterface $deleteBiometricDeviceService;

    /** @var FindAttendanceRecordServiceInterface&MockObject */
    private FindAttendanceRecordServiceInterface $findAttendanceRecordService;

    /** @var UpdateAttendanceRecordServiceInterface&MockObject */
    private UpdateAttendanceRecordServiceInterface $updateAttendanceRecordService;

    /** @var ProcessAttendanceServiceInterface&MockObject */
    private ProcessAttendanceServiceInterface $processAttendanceService;

    /** @var SubmitPerformanceReviewServiceInterface&MockObject */
    private SubmitPerformanceReviewServiceInterface $submitPerformanceReviewService;

    /** @var CreatePayrollRunServiceInterface&MockObject */
    private CreatePayrollRunServiceInterface $createPayrollRunService;

    /** @var FindAttendanceLogServiceInterface&MockObject */
    private FindAttendanceLogServiceInterface $findAttendanceLogService;

    /** @var CreateAttendanceLogServiceInterface&MockObject */
    private CreateAttendanceLogServiceInterface $createAttendanceLogService;

    /** @var FindLeaveBalanceServiceInterface&MockObject */
    private FindLeaveBalanceServiceInterface $findLeaveBalanceService;

    /** @var FindLeavePolicyServiceInterface&MockObject */
    private FindLeavePolicyServiceInterface $findLeavePolicyService;

    /** @var CreateLeavePolicyServiceInterface&MockObject */
    private CreateLeavePolicyServiceInterface $createLeavePolicyService;

    /** @var UpdateLeavePolicyServiceInterface&MockObject */
    private UpdateLeavePolicyServiceInterface $updateLeavePolicyService;

    /** @var FindPayrollItemServiceInterface&MockObject */
    private FindPayrollItemServiceInterface $findPayrollItemService;

    /** @var CreatePayrollItemServiceInterface&MockObject */
    private CreatePayrollItemServiceInterface $createPayrollItemService;

    /** @var UpdatePayrollItemServiceInterface&MockObject */
    private UpdatePayrollItemServiceInterface $updatePayrollItemService;

    /** @var FindPayslipServiceInterface&MockObject */
    private FindPayslipServiceInterface $findPayslipService;

    /** @var FindPerformanceCycleServiceInterface&MockObject */
    private FindPerformanceCycleServiceInterface $findPerformanceCycleService;

    /** @var CreatePerformanceCycleServiceInterface&MockObject */
    private CreatePerformanceCycleServiceInterface $createPerformanceCycleService;

    /** @var UpdatePerformanceCycleServiceInterface&MockObject */
    private UpdatePerformanceCycleServiceInterface $updatePerformanceCycleService;

    /** @var CreatePerformanceReviewServiceInterface&MockObject */
    private CreatePerformanceReviewServiceInterface $createPerformanceReviewService;

    /** @var UpdatePerformanceReviewServiceInterface&MockObject */
    private UpdatePerformanceReviewServiceInterface $updatePerformanceReviewService;

    /** @var FindPerformanceReviewServiceInterface&MockObject */
    private FindPerformanceReviewServiceInterface $findPerformanceReviewService;

    /** @var FindEmployeeDocumentServiceInterface&MockObject */
    private FindEmployeeDocumentServiceInterface $findEmployeeDocumentService;

    /** @var StoreEmployeeDocumentServiceInterface&MockObject */
    private StoreEmployeeDocumentServiceInterface $storeEmployeeDocumentService;

    /** @var DeleteEmployeeDocumentServiceInterface&MockObject */
    private DeleteEmployeeDocumentServiceInterface $deleteEmployeeDocumentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clearRoutesCacheOnce();

        // — Shift services —
        $this->findShiftService = $this->createMock(FindShiftServiceInterface::class);
        $this->createShiftService = $this->createMock(CreateShiftServiceInterface::class);
        $this->updateShiftService = $this->createMock(UpdateShiftServiceInterface::class);
        $this->deleteShiftService = $this->createMock(DeleteShiftServiceInterface::class);
        $this->assignShiftService = $this->createMock(AssignShiftServiceInterface::class);

        $this->app->instance(FindShiftServiceInterface::class, $this->findShiftService);
        $this->app->instance(CreateShiftServiceInterface::class, $this->createShiftService);
        $this->app->instance(UpdateShiftServiceInterface::class, $this->updateShiftService);
        $this->app->instance(DeleteShiftServiceInterface::class, $this->deleteShiftService);
        $this->app->instance(AssignShiftServiceInterface::class, $this->assignShiftService);

        // — LeaveType services —
        $this->findLeaveTypeService = $this->createMock(FindLeaveTypeServiceInterface::class);
        $this->createLeaveTypeService = $this->createMock(CreateLeaveTypeServiceInterface::class);

        $this->updateLeaveTypeService = $this->createMock(UpdateLeaveTypeServiceInterface::class);
        $this->deleteLeaveTypeService = $this->createMock(DeleteLeaveTypeServiceInterface::class);

        $this->app->instance(FindLeaveTypeServiceInterface::class, $this->findLeaveTypeService);
        $this->app->instance(CreateLeaveTypeServiceInterface::class, $this->createLeaveTypeService);
        $this->app->instance(UpdateLeaveTypeServiceInterface::class, $this->updateLeaveTypeService);
        $this->app->instance(DeleteLeaveTypeServiceInterface::class, $this->deleteLeaveTypeService);

        // — LeaveRequest services —
        $this->findLeaveRequestService = $this->createMock(FindLeaveRequestServiceInterface::class);
        $this->submitLeaveRequestService = $this->createMock(SubmitLeaveRequestServiceInterface::class);
        $this->approveLeaveRequestService = $this->createMock(ApproveLeaveRequestServiceInterface::class);
        $this->rejectLeaveRequestService = $this->createMock(RejectLeaveRequestServiceInterface::class);
        $this->cancelLeaveRequestService = $this->createMock(CancelLeaveRequestServiceInterface::class);

        $this->app->instance(FindLeaveRequestServiceInterface::class, $this->findLeaveRequestService);
        $this->app->instance(SubmitLeaveRequestServiceInterface::class, $this->submitLeaveRequestService);
        $this->app->instance(ApproveLeaveRequestServiceInterface::class, $this->approveLeaveRequestService);
        $this->app->instance(RejectLeaveRequestServiceInterface::class, $this->rejectLeaveRequestService);
        $this->app->instance(CancelLeaveRequestServiceInterface::class, $this->cancelLeaveRequestService);

        // — PayrollRun services —
        $this->findPayrollRunService = $this->createMock(FindPayrollRunServiceInterface::class);
        $this->createPayrollRunService = $this->createMock(CreatePayrollRunServiceInterface::class);
        $this->approvePayrollRunService = $this->createMock(ApprovePayrollRunServiceInterface::class);
        $this->processPayrollRunService = $this->createMock(ProcessPayrollRunServiceInterface::class);

        $this->app->instance(FindPayrollRunServiceInterface::class, $this->findPayrollRunService);
        $this->app->instance(CreatePayrollRunServiceInterface::class, $this->createPayrollRunService);
        $this->app->instance(ApprovePayrollRunServiceInterface::class, $this->approvePayrollRunService);
        $this->app->instance(ProcessPayrollRunServiceInterface::class, $this->processPayrollRunService);

        // — AttendanceLog services —
        $this->findAttendanceLogService = $this->createMock(FindAttendanceLogServiceInterface::class);
        $this->createAttendanceLogService = $this->createMock(CreateAttendanceLogServiceInterface::class);
        $this->app->instance(FindAttendanceLogServiceInterface::class, $this->findAttendanceLogService);
        $this->app->instance(CreateAttendanceLogServiceInterface::class, $this->createAttendanceLogService);

        // — LeaveBalance services —
        $this->findLeaveBalanceService = $this->createMock(FindLeaveBalanceServiceInterface::class);
        $this->app->instance(FindLeaveBalanceServiceInterface::class, $this->findLeaveBalanceService);

        // — LeavePolicy services —
        $this->findLeavePolicyService = $this->createMock(FindLeavePolicyServiceInterface::class);
        $this->createLeavePolicyService = $this->createMock(CreateLeavePolicyServiceInterface::class);
        $this->updateLeavePolicyService = $this->createMock(UpdateLeavePolicyServiceInterface::class);
        $this->app->instance(FindLeavePolicyServiceInterface::class, $this->findLeavePolicyService);
        $this->app->instance(CreateLeavePolicyServiceInterface::class, $this->createLeavePolicyService);
        $this->app->instance(UpdateLeavePolicyServiceInterface::class, $this->updateLeavePolicyService);

        // — AttendanceRecord services —
        $this->findAttendanceRecordService = $this->createMock(FindAttendanceRecordServiceInterface::class);
        $this->updateAttendanceRecordService = $this->createMock(UpdateAttendanceRecordServiceInterface::class);
        $this->app->instance(FindAttendanceRecordServiceInterface::class, $this->findAttendanceRecordService);
        $this->app->instance(UpdateAttendanceRecordServiceInterface::class, $this->updateAttendanceRecordService);

        // — ProcessAttendance —
        $this->processAttendanceService = $this->createMock(ProcessAttendanceServiceInterface::class);
        $this->app->instance(ProcessAttendanceServiceInterface::class, $this->processAttendanceService);

        // — BiometricDevice services —
        $this->findBiometricDeviceService = $this->createMock(FindBiometricDeviceServiceInterface::class);
        $this->syncBiometricDeviceService = $this->createMock(SyncBiometricDeviceServiceInterface::class);
        $this->app->instance(FindBiometricDeviceServiceInterface::class, $this->findBiometricDeviceService);
        $this->createBiometricDeviceService = $this->createMock(CreateBiometricDeviceServiceInterface::class);
        $this->updateBiometricDeviceService = $this->createMock(UpdateBiometricDeviceServiceInterface::class);
        $this->deleteBiometricDeviceService = $this->createMock(DeleteBiometricDeviceServiceInterface::class);
        $this->app->instance(CreateBiometricDeviceServiceInterface::class, $this->createBiometricDeviceService);
        $this->app->instance(UpdateBiometricDeviceServiceInterface::class, $this->updateBiometricDeviceService);
        $this->app->instance(DeleteBiometricDeviceServiceInterface::class, $this->deleteBiometricDeviceService);
        $this->app->instance(SyncBiometricDeviceServiceInterface::class, $this->syncBiometricDeviceService);

        // — PayrollItem services —
        $this->findPayrollItemService = $this->createMock(FindPayrollItemServiceInterface::class);
        $this->createPayrollItemService = $this->createMock(CreatePayrollItemServiceInterface::class);
        $this->updatePayrollItemService = $this->createMock(UpdatePayrollItemServiceInterface::class);
        $this->app->instance(FindPayrollItemServiceInterface::class, $this->findPayrollItemService);
        $this->app->instance(CreatePayrollItemServiceInterface::class, $this->createPayrollItemService);
        $this->app->instance(UpdatePayrollItemServiceInterface::class, $this->updatePayrollItemService);

        // — Payslip services —
        $this->findPayslipService = $this->createMock(FindPayslipServiceInterface::class);
        $this->app->instance(FindPayslipServiceInterface::class, $this->findPayslipService);

        // — PerformanceCycle services —
        $this->findPerformanceCycleService = $this->createMock(FindPerformanceCycleServiceInterface::class);
        $this->createPerformanceCycleService = $this->createMock(CreatePerformanceCycleServiceInterface::class);
        $this->updatePerformanceCycleService = $this->createMock(UpdatePerformanceCycleServiceInterface::class);
        $this->app->instance(FindPerformanceCycleServiceInterface::class, $this->findPerformanceCycleService);
        $this->app->instance(CreatePerformanceCycleServiceInterface::class, $this->createPerformanceCycleService);
        $this->app->instance(UpdatePerformanceCycleServiceInterface::class, $this->updatePerformanceCycleService);

        // — PerformanceReview services —
        $this->createPerformanceReviewService = $this->createMock(CreatePerformanceReviewServiceInterface::class);
        $this->updatePerformanceReviewService = $this->createMock(UpdatePerformanceReviewServiceInterface::class);
        $this->findPerformanceReviewService = $this->createMock(FindPerformanceReviewServiceInterface::class);
        $this->submitPerformanceReviewService = $this->createMock(SubmitPerformanceReviewServiceInterface::class);
        $this->app->instance(CreatePerformanceReviewServiceInterface::class, $this->createPerformanceReviewService);
        $this->app->instance(UpdatePerformanceReviewServiceInterface::class, $this->updatePerformanceReviewService);
        $this->app->instance(FindPerformanceReviewServiceInterface::class, $this->findPerformanceReviewService);
        $this->app->instance(SubmitPerformanceReviewServiceInterface::class, $this->submitPerformanceReviewService);

        // — EmployeeDocument services —
        $this->findEmployeeDocumentService = $this->createMock(FindEmployeeDocumentServiceInterface::class);
        $this->storeEmployeeDocumentService = $this->createMock(StoreEmployeeDocumentServiceInterface::class);
        $this->deleteEmployeeDocumentService = $this->createMock(DeleteEmployeeDocumentServiceInterface::class);
        $this->app->instance(FindEmployeeDocumentServiceInterface::class, $this->findEmployeeDocumentService);
        $this->app->instance(StoreEmployeeDocumentServiceInterface::class, $this->storeEmployeeDocumentService);
        $this->app->instance(DeleteEmployeeDocumentServiceInterface::class, $this->deleteEmployeeDocumentService);

        // — Tenant config (required by resolve.tenant middleware) —
        $tenantConfigClient = $this->createMock(TenantConfigClientInterface::class);
        $tenantConfigClient->method('getConfig')->willReturn(null);
        $this->app->instance(TenantConfigClientInterface::class, $tenantConfigClient);
        $this->app->instance(TenantConfigManagerInterface::class, $this->createMock(TenantConfigManagerInterface::class));

        // — PresenceVerifier for validation rules referencing DB —
        $presenceVerifier = $this->createMock(PresenceVerifierInterface::class);
        $presenceVerifier->method('getCount')->willReturn(1);
        $presenceVerifier->method('getMultiCount')->willReturn(1);
        $this->app->instance(PresenceVerifierInterface::class, $presenceVerifier);
        $this->app['validator']->setPresenceVerifier($presenceVerifier);

        config()->set('auth.guards.api.driver', 'session');

        // — Authenticated user —
        $user = new UserModel([
            'id' => 301,
            'tenant_id' => 7,
            'email' => 'hr.test@example.com',
            'password' => 'secret',
            'first_name' => 'HR',
            'last_name' => 'Tester',
        ]);
        $user->setAttribute('id', 301);
        $user->setAttribute('tenant_id', 7);

        $this->actingAs($user, 'api');
    }

    // =========================================================================
    // SHIFT endpoints
    // =========================================================================

    public function test_authenticated_index_shifts_returns_success(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [$this->buildShift(id: 1)],
            total: 1,
            perPage: 15,
            currentPage: 1,
        );

        $this->findShiftService->expects($this->once())->method('list')->willReturn($paginator);

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/shifts')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 1);
    }

    public function test_authenticated_show_shift_returns_success(): void
    {
        $this->findShiftService->expects($this->once())->method('find')->with(1)->willReturn($this->buildShift(id: 1));

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/shifts/1')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 1);
    }

    public function test_show_shift_returns_404_when_not_found(): void
    {
        $this->findShiftService->method('find')->willReturn(null);

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/shifts/9999')
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    public function test_store_shift_returns_201_on_success(): void
    {
        $this->createShiftService->expects($this->once())->method('execute')->willReturn($this->buildShift(id: 2));

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/shifts', [
                'tenant_id' => 7,
                'name' => 'Morning Shift',
                'code' => 'MS-01',
                'shift_type' => 'regular',
                'start_time' => '08:00',
                'end_time' => '17:00',
                'break_duration' => 60,
                'work_days' => [1, 2, 3, 4, 5],
                'grace_minutes' => 10,
                'overtime_threshold' => 30,
            ])
            ->assertStatus(HttpResponse::HTTP_CREATED)
            ->assertJsonPath('data.id', 2);
    }

    public function test_store_shift_returns_422_when_required_fields_missing(): void
    {
        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/shifts', [])
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonStructure(['message', 'errors']);
    }

    public function test_delete_shift_returns_success(): void
    {
        $this->findShiftService->method('find')->willReturn($this->buildShift(id: 3));
        $this->deleteShiftService->expects($this->once())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->deleteJson('/api/hr/shifts/3')
            ->assertStatus(HttpResponse::HTTP_NO_CONTENT);
    }

    public function test_assign_shift_returns_422_when_required_fields_missing(): void
    {
        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/shifts/3/assign', [])
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['employee_id', 'effective_from']);
    }

    public function test_assign_shift_returns_201_on_success(): void
    {
        $this->findShiftService->method('find')->willReturn($this->buildShift(id: 3));
        $this->assignShiftService->expects($this->once())->method('execute')->willReturn($this->buildShiftAssignment(id: 33, shiftId: 3));

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/shifts/3/assign', [
                'tenant_id' => 7,
                'employee_id' => 101,
                'effective_from' => '2026-06-01',
            ])
            ->assertStatus(HttpResponse::HTTP_CREATED)
            ->assertJsonPath('data.shift_id', 3)
            ->assertJsonPath('data.employee_id', 101);
    }

    public function test_assign_shift_returns_404_when_not_found(): void
    {
        $this->findShiftService->method('find')->willReturn(null);
        $this->assignShiftService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/shifts/9999/assign', [
                'tenant_id' => 7,
                'employee_id' => 101,
                'effective_from' => '2026-06-01',
            ])
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    // =========================================================================
    // LEAVE TYPE endpoints
    // =========================================================================

    public function test_authenticated_index_leave_types_returns_success(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [$this->buildLeaveType(id: 10)],
            total: 1,
            perPage: 15,
            currentPage: 1,
        );

        $this->findLeaveTypeService->expects($this->once())->method('list')->willReturn($paginator);

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/leave-types')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 10);
    }

    public function test_authenticated_show_leave_type_returns_success(): void
    {
        $this->findLeaveTypeService->expects($this->once())->method('find')->with(10)->willReturn($this->buildLeaveType(id: 10));

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/leave-types/10')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 10);
    }

    public function test_show_leave_type_returns_404_when_not_found(): void
    {
        $this->findLeaveTypeService->method('find')->willReturn(null);

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/leave-types/9999')
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    public function test_store_leave_type_returns_201(): void
    {
        $this->createLeaveTypeService->expects($this->once())->method('execute')->willReturn($this->buildLeaveType(id: 10));

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/leave-types', [
                'tenant_id' => 7,
                'name' => 'Annual Leave',
                'code' => 'AL',
            ])
            ->assertStatus(HttpResponse::HTTP_CREATED)
            ->assertJsonPath('data.id', 10);
    }

    public function test_store_leave_type_returns_422_when_required_fields_missing(): void
    {
        $this->createLeaveTypeService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/leave-types', [])
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['name', 'code']);
    }

    // =========================================================================
    // LEAVE REQUEST endpoints
    // =========================================================================

    public function test_authenticated_index_leave_requests_returns_success(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [$this->buildLeaveRequest(id: 20)],
            total: 1,
            perPage: 15,
            currentPage: 1,
        );

        $this->findLeaveRequestService->expects($this->once())->method('list')->willReturn($paginator);

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/leave-requests')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 20);
    }

    public function test_authenticated_show_leave_request_returns_success(): void
    {
        $this->findLeaveRequestService->expects($this->once())->method('find')->with(20)->willReturn($this->buildLeaveRequest(id: 20));

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/leave-requests/20')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 20);
    }

    public function test_show_leave_request_returns_404_when_not_found(): void
    {
        $this->findLeaveRequestService->method('find')->willReturn(null);

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/leave-requests/9999')
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    public function test_approve_leave_request_returns_success(): void
    {
        $this->findLeaveRequestService->method('find')->willReturn($this->buildLeaveRequest(id: 20, status: LeaveRequestStatus::APPROVED));
        $this->approveLeaveRequestService->expects($this->once())->method('execute')->willReturn($this->buildLeaveRequest(id: 20, status: LeaveRequestStatus::APPROVED));

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/leave-requests/20/approve', ['approver_id' => 301, 'approver_note' => 'Approved.'])
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 20);
    }

    public function test_reject_leave_request_returns_success(): void
    {
        $this->findLeaveRequestService->method('find')->willReturn($this->buildLeaveRequest(id: 20));
        $this->rejectLeaveRequestService->expects($this->once())->method('execute')->willReturn($this->buildLeaveRequest(id: 20, status: LeaveRequestStatus::REJECTED));

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/leave-requests/20/reject', ['approver_id' => 301, 'reason' => 'Rejected due to policy.'])
            ->assertStatus(HttpResponse::HTTP_OK);
    }

    public function test_approve_leave_request_returns_422_when_required_fields_missing(): void
    {
        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/leave-requests/20/approve', [])
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['approver_id']);
    }

    public function test_approve_leave_request_returns_404_when_not_found(): void
    {
        $this->findLeaveRequestService->method('find')->willReturn(null);
        $this->approveLeaveRequestService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/leave-requests/9999/approve', ['approver_id' => 301])
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    public function test_reject_leave_request_returns_422_when_reason_missing(): void
    {
        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/leave-requests/20/reject', ['approver_id' => 301])
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_reject_leave_request_returns_404_when_not_found(): void
    {
        $this->findLeaveRequestService->method('find')->willReturn(null);
        $this->rejectLeaveRequestService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/leave-requests/9999/reject', ['approver_id' => 301, 'reason' => 'N/A'])
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    public function test_cancel_leave_request_returns_success_message(): void
    {
        $this->findLeaveRequestService->method('find')->willReturn($this->buildLeaveRequest(id: 20));
        $this->cancelLeaveRequestService->expects($this->once())->method('execute')->with(['id' => 20]);

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/leave-requests/20/cancel', [])
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('message', 'Leave request cancelled.');
    }

    public function test_cancel_leave_request_returns_404_when_not_found(): void
    {
        $this->findLeaveRequestService->method('find')->willReturn(null);
        $this->cancelLeaveRequestService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/leave-requests/9999/cancel', [])
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    public function test_store_leave_request_returns_201(): void
    {
        $this->submitLeaveRequestService->expects($this->once())->method('execute')->willReturn($this->buildLeaveRequest(id: 21));

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/leave-requests', [
                'tenant_id'     => 7,
                'employee_id'   => 101,
                'leave_type_id' => 3,
                'start_date'    => '2026-07-01',
                'end_date'      => '2026-07-05',
            ])
            ->assertStatus(HttpResponse::HTTP_CREATED)
            ->assertJsonPath('data.id', 21);
    }

    public function test_store_leave_request_returns_422_when_required_fields_missing(): void
    {
        $this->submitLeaveRequestService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/leave-requests', [])
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['employee_id', 'leave_type_id', 'start_date', 'end_date']);
    }

    public function test_update_leave_request_returns_updated_resource(): void
    {
        $this->findLeaveRequestService->method('find')->willReturn($this->buildLeaveRequest(id: 20));
        $this->submitLeaveRequestService->expects($this->once())->method('execute')->willReturn($this->buildLeaveRequest(id: 20));

        $this->withHeader('X-Tenant-ID', '7')
            ->putJson('/api/hr/leave-requests/20', ['reason' => 'Updated reason'])
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 20);
    }

    public function test_update_leave_request_returns_404_when_not_found(): void
    {
        $this->findLeaveRequestService->method('find')->willReturn(null);
        $this->submitLeaveRequestService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->putJson('/api/hr/leave-requests/9999', ['reason' => 'Ghost'])
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    public function test_delete_leave_request_returns_204(): void
    {
        $this->findLeaveRequestService->method('find')->willReturn($this->buildLeaveRequest(id: 20));
        $this->cancelLeaveRequestService->expects($this->once())->method('execute')->with(['id' => 20]);

        $this->withHeader('X-Tenant-ID', '7')
            ->deleteJson('/api/hr/leave-requests/20')
            ->assertStatus(HttpResponse::HTTP_NO_CONTENT);
    }

    public function test_delete_leave_request_returns_404_when_not_found(): void
    {
        $this->findLeaveRequestService->method('find')->willReturn(null);
        $this->cancelLeaveRequestService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->deleteJson('/api/hr/leave-requests/9999')
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    // =========================================================================
    // PAYROLL RUN endpoints
    // =========================================================================

    public function test_authenticated_index_payroll_runs_returns_success(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [$this->buildPayrollRun(id: 30)],
            total: 1,
            perPage: 15,
            currentPage: 1,
        );

        $this->findPayrollRunService->expects($this->once())->method('list')->willReturn($paginator);

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/payroll-runs')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 30);
    }

    public function test_authenticated_show_payroll_run_returns_success(): void
    {
        $this->findPayrollRunService->expects($this->once())->method('find')->with(30)->willReturn($this->buildPayrollRun(id: 30));

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/payroll-runs/30')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 30);
    }

    public function test_show_payroll_run_returns_404_when_not_found(): void
    {
        $this->findPayrollRunService->method('find')->willReturn(null);

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/payroll-runs/9999')
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    public function test_store_payroll_run_returns_201(): void
    {
        $this->createPayrollRunService->expects($this->once())->method('execute')->willReturn($this->buildPayrollRun(id: 31));

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/payroll-runs', [
                'tenant_id' => 7,
                'period_start' => '2026-07-01',
                'period_end' => '2026-07-31',
            ])
            ->assertStatus(HttpResponse::HTTP_CREATED)
            ->assertJsonPath('data.id', 31);
    }

    public function test_store_payroll_run_returns_422_when_required_fields_missing(): void
    {
        $this->createPayrollRunService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/payroll-runs', [])
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['period_start', 'period_end']);
    }

    public function test_update_payroll_run_returns_updated_resource(): void
    {
        $this->findPayrollRunService->method('find')->willReturn($this->buildPayrollRun(id: 30));
        $this->createPayrollRunService->expects($this->once())->method('execute')->willReturn($this->buildPayrollRun(id: 30));

        $this->withHeader('X-Tenant-ID', '7')
            ->putJson('/api/hr/payroll-runs/30', ['period_end' => '2026-08-31'])
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 30);
    }

    public function test_update_payroll_run_returns_404_when_not_found(): void
    {
        $this->findPayrollRunService->method('find')->willReturn(null);
        $this->createPayrollRunService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->putJson('/api/hr/payroll-runs/9999', ['period_end' => '2026-08-31'])
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    public function test_delete_payroll_run_returns_204(): void
    {
        $this->findPayrollRunService->method('find')->willReturn($this->buildPayrollRun(id: 30));

        $this->withHeader('X-Tenant-ID', '7')
            ->deleteJson('/api/hr/payroll-runs/30')
            ->assertStatus(HttpResponse::HTTP_NO_CONTENT);
    }

    public function test_delete_payroll_run_returns_404_when_not_found(): void
    {
        $this->findPayrollRunService->method('find')->willReturn(null);

        $this->withHeader('X-Tenant-ID', '7')
            ->deleteJson('/api/hr/payroll-runs/9999')
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    public function test_approve_payroll_run_returns_422_when_required_fields_missing(): void
    {
        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/payroll-runs/30/approve', [])
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['approved_by']);
    }

    public function test_approve_payroll_run_returns_success(): void
    {
        $this->findPayrollRunService->method('find')->willReturn($this->buildPayrollRun(id: 30));
        $this->approvePayrollRunService->expects($this->once())->method('execute')->willReturn($this->buildPayrollRun(id: 30, status: PayrollRunStatus::PROCESSING));

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/payroll-runs/30/approve', ['approved_by' => 301])
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 30);
    }

    public function test_approve_payroll_run_returns_404_when_not_found(): void
    {
        $this->findPayrollRunService->method('find')->willReturn(null);
        $this->approvePayrollRunService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/payroll-runs/9999/approve', ['approved_by' => 301])
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    public function test_process_payroll_run_returns_success(): void
    {
        $this->findPayrollRunService->method('find')->willReturn($this->buildPayrollRun(id: 30));
        $this->processPayrollRunService->expects($this->once())->method('execute')->willReturn($this->buildPayrollRun(id: 30, status: PayrollRunStatus::PROCESSING));

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/payroll-runs/30/process', [])
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 30);
    }

    public function test_process_payroll_run_returns_404_when_not_found(): void
    {
        $this->findPayrollRunService->method('find')->willReturn(null);
        $this->processPayrollRunService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/payroll-runs/9999/process', [])
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    public function test_submit_performance_review_returns_success(): void
    {
        /** @var FindPerformanceReviewServiceInterface&MockObject $findPerformanceReviewService */
        $findPerformanceReviewService = app(FindPerformanceReviewServiceInterface::class);
        $findPerformanceReviewService->method('find')->willReturn($this->buildPerformanceReview(id: 40));

        $this->submitPerformanceReviewService->expects($this->once())->method('execute')->willReturn($this->buildPerformanceReview(id: 40, status: 'submitted'));

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/performance-reviews/40/submit', [])
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 40)
            ->assertJsonPath('data.status', 'submitted');
    }

    public function test_submit_performance_review_returns_404_when_not_found(): void
    {
        $this->findPerformanceReviewService->method('find')->willReturn(null);
        $this->submitPerformanceReviewService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/performance-reviews/9999/submit', [])
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    public function test_sync_biometric_device_returns_success(): void
    {
        $this->findBiometricDeviceService->method('find')->willReturn($this->buildBiometricDevice(id: 50));
        $this->syncBiometricDeviceService->expects($this->once())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/biometric-devices/50/sync', [])
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('message', 'Device sync initiated.');
    }

    public function test_sync_biometric_device_returns_404_when_not_found(): void
    {
        $this->findBiometricDeviceService->method('find')->willReturn(null);
        $this->syncBiometricDeviceService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/biometric-devices/9999/sync', [])
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    public function test_authenticated_index_biometric_devices_returns_success(): void
    {
        $this->findBiometricDeviceService->expects($this->once())->method('list')->willReturn([
            $this->buildBiometricDevice(id: 50),
        ]);

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/biometric-devices')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 50);
    }

    public function test_authenticated_show_biometric_device_returns_success(): void
    {
        $this->findBiometricDeviceService->expects($this->once())->method('find')->with(50)->willReturn($this->buildBiometricDevice(id: 50));

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/biometric-devices/50')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 50);
    }

    public function test_show_biometric_device_returns_404_when_not_found(): void
    {
        $this->findBiometricDeviceService->method('find')->willReturn(null);

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/biometric-devices/9999')
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    public function test_store_biometric_device_returns_201(): void
    {
        $this->createBiometricDeviceService->expects($this->once())->method('execute')->willReturn($this->buildBiometricDevice(id: 51));

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/biometric-devices', [
                'tenant_id' => 7,
                'name'      => 'Reception Device',
                'code'      => 'RD-01',
            ])
            ->assertStatus(HttpResponse::HTTP_CREATED)
            ->assertJsonPath('data.id', 51);
    }

    public function test_store_biometric_device_returns_422_when_required_fields_missing(): void
    {
        $this->createBiometricDeviceService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/biometric-devices', [])
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['name', 'code']);
    }

    public function test_update_biometric_device_returns_updated_resource(): void
    {
        $this->findBiometricDeviceService->method('find')->willReturn($this->buildBiometricDevice(id: 50));
        $this->updateBiometricDeviceService->expects($this->once())->method('execute')->willReturn($this->buildBiometricDevice(id: 50));

        $this->withHeader('X-Tenant-ID', '7')
            ->putJson('/api/hr/biometric-devices/50', ['name' => 'Updated Device'])
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 50);
    }

    public function test_update_biometric_device_returns_404_when_not_found(): void
    {
        $this->findBiometricDeviceService->method('find')->willReturn(null);
        $this->updateBiometricDeviceService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->putJson('/api/hr/biometric-devices/9999', ['name' => 'Ghost'])
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    public function test_delete_biometric_device_returns_204(): void
    {
        $this->findBiometricDeviceService->method('find')->willReturn($this->buildBiometricDevice(id: 50));
        $this->deleteBiometricDeviceService->expects($this->once())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->deleteJson('/api/hr/biometric-devices/50')
            ->assertStatus(HttpResponse::HTTP_NO_CONTENT);
    }

    public function test_delete_biometric_device_returns_404_when_not_found(): void
    {
        $this->findBiometricDeviceService->method('find')->willReturn(null);
        $this->deleteBiometricDeviceService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->deleteJson('/api/hr/biometric-devices/9999')
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    public function test_process_attendance_returns_success_message(): void
    {
        $this->processAttendanceService->expects($this->once())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/attendance-records/process', [
                'tenant_id' => 7,
                'date' => '2026-06-15',
            ])
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('message', 'Attendance processed successfully.');
    }

    public function test_process_attendance_returns_422_when_required_fields_missing(): void
    {
        $this->processAttendanceService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/attendance-records/process', [])
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['date']);
    }

    public function test_authenticated_index_attendance_records_returns_success(): void
    {
        $this->findAttendanceRecordService->expects($this->once())->method('list')->willReturn([
            $this->buildAttendanceRecord(id: 60),
        ]);

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/attendance-records')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 60);
    }

    public function test_authenticated_show_attendance_record_returns_success(): void
    {
        $this->findAttendanceRecordService->expects($this->once())->method('find')->with(60)->willReturn($this->buildAttendanceRecord(id: 60));

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/attendance-records/60')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 60);
    }

    public function test_show_attendance_record_returns_404_when_not_found(): void
    {
        $this->findAttendanceRecordService->method('find')->willReturn(null);

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/attendance-records/9999')
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    public function test_update_attendance_record_returns_updated_resource(): void
    {
        $this->findAttendanceRecordService->method('find')->willReturn($this->buildAttendanceRecord(id: 60));
        $this->updateAttendanceRecordService->expects($this->once())->method('execute')->willReturn($this->buildAttendanceRecord(id: 60));

        $this->withHeader('X-Tenant-ID', '7')
            ->putJson('/api/hr/attendance-records/60', ['status' => 'present'])
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 60);
    }

    public function test_update_attendance_record_returns_404_when_not_found(): void
    {
        $this->findAttendanceRecordService->method('find')->willReturn(null);
        $this->updateAttendanceRecordService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->putJson('/api/hr/attendance-records/9999', ['status' => 'present'])
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    // =========================================================================
    // Entity builders
    // =========================================================================

    private function buildShift(int $id): Shift
    {
        $now = new \DateTimeImmutable;

        return new Shift(
            tenantId: 7,
            name: 'Morning Shift',
            code: 'MS-01',
            shiftType: ShiftType::REGULAR,
            startTime: '08:00',
            endTime: '17:00',
            breakDuration: 60,
            workDays: [1, 2, 3, 4, 5],
            graceMinutes: 10,
            overtimeThreshold: 30,
            isNightShift: false,
            metadata: [],
            isActive: true,
            createdAt: $now,
            updatedAt: $now,
            id: $id,
        );
    }

    private function buildLeaveType(int $id): LeaveType
    {
        $now = new \DateTimeImmutable;

        return new LeaveType(
            tenantId: 7,
            name: 'Annual Leave',
            code: 'AL',
            description: 'Annual paid leave',
            maxDaysPerYear: 21.0,
            carryForwardDays: 5.0,
            isPaid: true,
            requiresApproval: true,
            applicableGender: null,
            minServiceDays: 90,
            isActive: true,
            metadata: [],
            createdAt: $now,
            updatedAt: $now,
            id: $id,
        );
    }

    private function buildLeaveRequest(int $id, LeaveRequestStatus $status = LeaveRequestStatus::PENDING): LeaveRequest
    {
        $now = new \DateTimeImmutable;

        return new LeaveRequest(
            tenantId: 7,
            employeeId: 101,
            leaveTypeId: 10,
            startDate: new \DateTimeImmutable('2026-05-01'),
            endDate: new \DateTimeImmutable('2026-05-05'),
            totalDays: 5.0,
            reason: 'Vacation',
            status: $status,
            approverId: null,
            approverNote: '',
            attachmentPath: null,
            metadata: [],
            createdAt: $now,
            updatedAt: $now,
            id: $id,
        );
    }

    private function buildPayrollRun(int $id, PayrollRunStatus $status = PayrollRunStatus::DRAFT): PayrollRun
    {
        $now = new \DateTimeImmutable;

        return new PayrollRun(
            tenantId: 7,
            periodStart: new \DateTimeImmutable('2026-05-01'),
            periodEnd: new \DateTimeImmutable('2026-05-31'),
            status: $status,
            processedAt: null,
            approvedAt: null,
            approvedBy: null,
            totalGross: '50000.000000',
            totalDeductions: '5000.000000',
            totalNet: '45000.000000',
            metadata: [],
            createdAt: $now,
            updatedAt: $now,
            id: $id,
        );
    }

    private function buildShiftAssignment(int $id, int $shiftId): ShiftAssignment
    {
        $now = new \DateTimeImmutable;

        return new ShiftAssignment(
            tenantId: 7,
            employeeId: 101,
            shiftId: $shiftId,
            effectiveFrom: new \DateTimeImmutable('2026-06-01'),
            effectiveTo: null,
            createdAt: $now,
            updatedAt: $now,
            id: $id,
        );
    }

    private function buildPerformanceReview(int $id, string $status = 'draft'): PerformanceReview
    {
        $now = new \DateTimeImmutable;

        return new PerformanceReview(
            tenantId: 7,
            employeeId: 101,
            cycleId: 11,
            reviewerId: 301,
            overallRating: PerformanceRating::MEETS_EXPECTATIONS,
            goals: ['Increase delivery predictability'],
            strengths: 'Ownership',
            improvements: 'Cross-team communication',
            reviewerComments: 'Solid performance.',
            employeeComments: 'Will improve communication.',
            status: $status,
            acknowledgedAt: null,
            metadata: [],
            createdAt: $now,
            updatedAt: $now,
            id: $id,
        );
    }

    private function buildBiometricDevice(int $id): BiometricDevice
    {
        $now = new \DateTimeImmutable;

        return new BiometricDevice(
            tenantId: 7,
            name: 'Main Gate Device',
            code: 'BG-01',
            deviceType: 'fingerprint',
            ipAddress: '10.0.0.10',
            port: 4370,
            location: 'HQ Main Gate',
            orgUnitId: null,
            status: BiometricDeviceStatus::ACTIVE,
            metadata: [],
            createdAt: $now,
            updatedAt: $now,
            id: $id,
        );
    }

    // =========================================================================
    // ATTENDANCE LOG endpoints
    // =========================================================================

    public function test_authenticated_index_attendance_logs_returns_success(): void
    {
        $this->findAttendanceLogService->expects($this->once())->method('list')->willReturn([
            $this->buildAttendanceLog(id: 1),
        ]);

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/attendance-logs')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 1);
    }

    public function test_authenticated_show_attendance_log_returns_success(): void
    {
        $this->findAttendanceLogService->expects($this->once())->method('find')->with(1)->willReturn($this->buildAttendanceLog(id: 1));

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/attendance-logs/1')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 1);
    }

    public function test_show_attendance_log_returns_404_when_not_found(): void
    {
        $this->findAttendanceLogService->method('find')->willReturn(null);

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/attendance-logs/9999')
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    public function test_store_attendance_log_returns_201(): void
    {
        $this->createAttendanceLogService->expects($this->once())->method('execute')->willReturn($this->buildAttendanceLog(id: 5));

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/attendance-logs', [
                'tenant_id' => 7,
                'employee_id' => 101,
                'punch_time' => '2026-06-15T08:00:00+00:00',
                'punch_type' => 'in',
            ])
            ->assertStatus(HttpResponse::HTTP_CREATED)
            ->assertJsonPath('data.id', 5);
    }

    public function test_store_attendance_log_returns_422_when_required_fields_missing(): void
    {
        $this->createAttendanceLogService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/attendance-logs', [])
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['employee_id', 'punch_time', 'punch_type']);
    }

    // =========================================================================
    // LEAVE BALANCE endpoints
    // =========================================================================

    public function test_authenticated_index_leave_balances_returns_success(): void
    {
        $this->findLeaveBalanceService->expects($this->once())->method('list')->willReturn([
            $this->buildLeaveBalance(id: 1),
        ]);

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/leave-balances')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 1);
    }

    public function test_authenticated_show_leave_balance_returns_success(): void
    {
        $this->findLeaveBalanceService->expects($this->once())->method('find')->with(1)->willReturn($this->buildLeaveBalance(id: 1));

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/leave-balances/1')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 1);
    }

    public function test_show_leave_balance_returns_404_when_not_found(): void
    {
        $this->findLeaveBalanceService->method('find')->willReturn(null);

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/leave-balances/9999')
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    // =========================================================================
    // LEAVE POLICY endpoints
    // =========================================================================

    public function test_authenticated_index_leave_policies_returns_success(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [$this->buildLeavePolicy(id: 1)],
            total: 1,
            perPage: 15,
            currentPage: 1,
        );

        $this->findLeavePolicyService->expects($this->once())->method('list')->willReturn($paginator);

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/leave-policies')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 1);
    }

    public function test_authenticated_show_leave_policy_returns_success(): void
    {
        $this->findLeavePolicyService->expects($this->once())->method('find')->with(1)->willReturn($this->buildLeavePolicy(id: 1));

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/leave-policies/1')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 1);
    }

    public function test_show_leave_policy_returns_404_when_not_found(): void
    {
        $this->findLeavePolicyService->method('find')->willReturn(null);

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/leave-policies/9999')
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    public function test_store_leave_policy_returns_201(): void
    {
        $this->createLeavePolicyService->expects($this->once())->method('execute')->willReturn($this->buildLeavePolicy(id: 7));

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/leave-policies', [
                'tenant_id' => 7,
                'leave_type_id' => 10,
                'name' => 'Standard Annual Leave Policy',
            ])
            ->assertStatus(HttpResponse::HTTP_CREATED)
            ->assertJsonPath('data.id', 7);
    }

    public function test_store_leave_policy_returns_422_when_required_fields_missing(): void
    {
        $this->createLeavePolicyService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/leave-policies', [])
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['leave_type_id', 'name']);
    }

    // =========================================================================
    // PAYROLL ITEM endpoints
    // =========================================================================

    public function test_authenticated_index_payroll_items_returns_success(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [$this->buildPayrollItem(id: 1)],
            total: 1,
            perPage: 15,
            currentPage: 1,
        );

        $this->findPayrollItemService->expects($this->once())->method('list')->willReturn($paginator);

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/payroll-items')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 1);
    }

    public function test_authenticated_show_payroll_item_returns_success(): void
    {
        $this->findPayrollItemService->expects($this->once())->method('find')->with(1)->willReturn($this->buildPayrollItem(id: 1));

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/payroll-items/1')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 1);
    }

    public function test_show_payroll_item_returns_404_when_not_found(): void
    {
        $this->findPayrollItemService->method('find')->willReturn(null);

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/payroll-items/9999')
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    public function test_store_payroll_item_returns_201(): void
    {
        $this->createPayrollItemService->expects($this->once())->method('execute')->willReturn($this->buildPayrollItem(id: 8));

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/payroll-items', [
                'tenant_id' => 7,
                'name' => 'Basic Salary',
                'code' => 'BASIC',
                'type' => 'earning',
                'calculation_type' => 'fixed',
                'value' => 50000,
            ])
            ->assertStatus(HttpResponse::HTTP_CREATED)
            ->assertJsonPath('data.id', 8);
    }

    public function test_store_payroll_item_returns_422_when_required_fields_missing(): void
    {
        $this->createPayrollItemService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/payroll-items', [])
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['name', 'code', 'type', 'calculation_type', 'value']);
    }

    // =========================================================================
    // PAYSLIP endpoints
    // =========================================================================

    public function test_authenticated_index_payslips_returns_success(): void
    {
        $this->findPayslipService->expects($this->once())->method('list')->willReturn([
            $this->buildPayslip(id: 1),
        ]);

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/payslips')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 1);
    }

    public function test_authenticated_show_payslip_returns_success(): void
    {
        $this->findPayslipService->expects($this->once())->method('find')->with(1)->willReturn($this->buildPayslip(id: 1));

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/payslips/1')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 1);
    }

    public function test_show_payslip_returns_404_when_not_found(): void
    {
        $this->findPayslipService->method('find')->willReturn(null);

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/payslips/9999')
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    // =========================================================================
    // PERFORMANCE CYCLE endpoints
    // =========================================================================

    public function test_authenticated_index_performance_cycles_returns_success(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [$this->buildPerformanceCycle(id: 1)],
            total: 1,
            perPage: 15,
            currentPage: 1,
        );

        $this->findPerformanceCycleService->expects($this->once())->method('list')->willReturn($paginator);

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/performance-cycles')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 1);
    }

    public function test_authenticated_show_performance_cycle_returns_success(): void
    {
        $this->findPerformanceCycleService->expects($this->once())->method('find')->with(1)->willReturn($this->buildPerformanceCycle(id: 1));

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/performance-cycles/1')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 1);
    }

    public function test_show_performance_cycle_returns_404_when_not_found(): void
    {
        $this->findPerformanceCycleService->method('find')->willReturn(null);

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/performance-cycles/9999')
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    public function test_store_performance_cycle_returns_201(): void
    {
        $this->createPerformanceCycleService->expects($this->once())->method('execute')->willReturn($this->buildPerformanceCycle(id: 11));

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/performance-cycles', [
                'tenant_id' => 7,
                'name' => 'Q2 2026 Performance Cycle',
                'period_start' => '2026-04-01',
                'period_end' => '2026-06-30',
            ])
            ->assertStatus(HttpResponse::HTTP_CREATED)
            ->assertJsonPath('data.id', 11);
    }

    public function test_store_performance_cycle_returns_422_when_required_fields_missing(): void
    {
        $this->createPerformanceCycleService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/performance-cycles', [])
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['name', 'period_start', 'period_end']);
    }

    // =========================================================================
    // PERFORMANCE REVIEW CRUD endpoints
    // =========================================================================

    public function test_authenticated_index_performance_reviews_returns_success(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [$this->buildPerformanceReview(id: 40)],
            total: 1,
            perPage: 15,
            currentPage: 1,
        );

        $this->findPerformanceReviewService->expects($this->once())->method('list')->willReturn($paginator);

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/performance-reviews')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 40);
    }

    public function test_authenticated_show_performance_review_returns_success(): void
    {
        $this->findPerformanceReviewService->expects($this->once())->method('find')->with(40)->willReturn($this->buildPerformanceReview(id: 40));

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/performance-reviews/40')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 40);
    }

    public function test_show_performance_review_returns_404_when_not_found(): void
    {
        $this->findPerformanceReviewService->method('find')->willReturn(null);

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/performance-reviews/9999')
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    public function test_store_performance_review_returns_201(): void
    {
        $this->createPerformanceReviewService->expects($this->once())->method('execute')->willReturn($this->buildPerformanceReview(id: 41));

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/performance-reviews', [
                'tenant_id' => 7,
                'employee_id' => 101,
                'cycle_id' => 11,
                'reviewer_id' => 301,
            ])
            ->assertStatus(HttpResponse::HTTP_CREATED)
            ->assertJsonPath('data.id', 41);
    }

    public function test_store_performance_review_returns_422_when_required_fields_missing(): void
    {
        $this->createPerformanceReviewService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/performance-reviews', [])
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['employee_id', 'cycle_id', 'reviewer_id']);
    }

    // =========================================================================
    // EMPLOYEE DOCUMENT endpoints
    // =========================================================================

    public function test_authenticated_index_employee_documents_returns_success(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [$this->buildEmployeeDocument(id: 1)],
            total: 1,
            perPage: 15,
            currentPage: 1,
        );

        $this->findEmployeeDocumentService->expects($this->once())->method('list')->willReturn($paginator);

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/employee-documents')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 1);
    }

    public function test_authenticated_show_employee_document_returns_success(): void
    {
        $this->findEmployeeDocumentService->expects($this->once())->method('find')->with(1)->willReturn($this->buildEmployeeDocument(id: 1));

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/employee-documents/1')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 1);
    }

    public function test_show_employee_document_returns_404_when_not_found(): void
    {
        $this->findEmployeeDocumentService->method('find')->willReturn(null);

        $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/hr/employee-documents/9999')
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    public function test_store_employee_document_returns_201(): void
    {
        $this->storeEmployeeDocumentService->expects($this->once())->method('execute')->willReturn($this->buildEmployeeDocument(id: 9));

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/employee-documents', [
                'tenant_id' => 7,
                'employee_id' => 101,
                'document_type' => 'contract',
                'title' => 'Employment Contract',
                'file_path' => 'documents/contract.pdf',
            ])
            ->assertStatus(HttpResponse::HTTP_CREATED)
            ->assertJsonPath('data.id', 9);
    }

    public function test_store_employee_document_returns_422_when_required_fields_missing(): void
    {
        $this->storeEmployeeDocumentService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/hr/employee-documents', [])
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['employee_id', 'document_type', 'title', 'file_path']);
    }

    // =========================================================================
    // UPDATE / DELETE endpoints
    // =========================================================================

    // — Shift update / delete —

    public function test_update_shift_returns_updated_resource(): void
    {
        $this->findShiftService->method('find')->willReturn($this->buildShift(id: 1));
        $this->updateShiftService->expects($this->once())->method('execute')->willReturn($this->buildShift(id: 1));

        $this->withHeader('X-Tenant-ID', '7')
            ->putJson('/api/hr/shifts/1', ['name' => 'Updated Morning Shift'])
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 1);
    }

    public function test_update_shift_returns_404_when_not_found(): void
    {
        $this->findShiftService->method('find')->willReturn(null);
        $this->updateShiftService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->putJson('/api/hr/shifts/9999', ['name' => 'Ghost Shift'])
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    public function test_delete_shift_returns_404_when_not_found(): void
    {
        $this->findShiftService->method('find')->willReturn(null);
        $this->deleteShiftService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->deleteJson('/api/hr/shifts/9999')
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    // — LeaveType update / delete —

    public function test_update_leave_type_returns_updated_resource(): void
    {
        $this->findLeaveTypeService->method('find')->willReturn($this->buildLeaveType(id: 10));
        $this->updateLeaveTypeService->expects($this->once())->method('execute')->willReturn($this->buildLeaveType(id: 10));

        $this->withHeader('X-Tenant-ID', '7')
            ->putJson('/api/hr/leave-types/10', ['name' => 'Updated Annual Leave'])
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 10);
    }

    public function test_update_leave_type_returns_404_when_not_found(): void
    {
        $this->findLeaveTypeService->method('find')->willReturn(null);
        $this->updateLeaveTypeService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->putJson('/api/hr/leave-types/9999', ['name' => 'Ghost'])
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    public function test_delete_leave_type_returns_204(): void
    {
        $this->findLeaveTypeService->method('find')->willReturn($this->buildLeaveType(id: 10));
        $this->deleteLeaveTypeService->expects($this->once())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->deleteJson('/api/hr/leave-types/10')
            ->assertStatus(HttpResponse::HTTP_NO_CONTENT);
    }

    public function test_delete_leave_type_returns_404_when_not_found(): void
    {
        $this->findLeaveTypeService->method('find')->willReturn(null);
        $this->deleteLeaveTypeService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->deleteJson('/api/hr/leave-types/9999')
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    // — LeavePolicy update / delete —

    public function test_update_leave_policy_returns_updated_resource(): void
    {
        $this->findLeavePolicyService->method('find')->willReturn($this->buildLeavePolicy(id: 1));
        $this->updateLeavePolicyService->expects($this->once())->method('execute')->willReturn($this->buildLeavePolicy(id: 1));

        $this->withHeader('X-Tenant-ID', '7')
            ->putJson('/api/hr/leave-policies/1', ['name' => 'Updated Leave Policy'])
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 1);
    }

    public function test_update_leave_policy_returns_404_when_not_found(): void
    {
        $this->findLeavePolicyService->method('find')->willReturn(null);
        $this->updateLeavePolicyService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->putJson('/api/hr/leave-policies/9999', ['name' => 'Ghost'])
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    public function test_delete_leave_policy_returns_204(): void
    {
        $this->findLeavePolicyService->method('find')->willReturn($this->buildLeavePolicy(id: 1));

        $this->withHeader('X-Tenant-ID', '7')
            ->deleteJson('/api/hr/leave-policies/1')
            ->assertStatus(HttpResponse::HTTP_NO_CONTENT);
    }

    public function test_delete_leave_policy_returns_404_when_not_found(): void
    {
        $this->findLeavePolicyService->method('find')->willReturn(null);

        $this->withHeader('X-Tenant-ID', '7')
            ->deleteJson('/api/hr/leave-policies/9999')
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    // — PayrollItem update / delete —

    public function test_update_payroll_item_returns_updated_resource(): void
    {
        $this->findPayrollItemService->method('find')->willReturn($this->buildPayrollItem(id: 8));
        $this->updatePayrollItemService->expects($this->once())->method('execute')->willReturn($this->buildPayrollItem(id: 8));

        $this->withHeader('X-Tenant-ID', '7')
            ->putJson('/api/hr/payroll-items/8', ['name' => 'Updated Salary'])
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 8);
    }

    public function test_update_payroll_item_returns_404_when_not_found(): void
    {
        $this->findPayrollItemService->method('find')->willReturn(null);
        $this->updatePayrollItemService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->putJson('/api/hr/payroll-items/9999', ['name' => 'Ghost'])
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    public function test_delete_payroll_item_returns_204(): void
    {
        $this->findPayrollItemService->method('find')->willReturn($this->buildPayrollItem(id: 8));

        $this->withHeader('X-Tenant-ID', '7')
            ->deleteJson('/api/hr/payroll-items/8')
            ->assertStatus(HttpResponse::HTTP_NO_CONTENT);
    }

    public function test_delete_payroll_item_returns_404_when_not_found(): void
    {
        $this->findPayrollItemService->method('find')->willReturn(null);

        $this->withHeader('X-Tenant-ID', '7')
            ->deleteJson('/api/hr/payroll-items/9999')
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    // — PerformanceCycle update / delete —

    public function test_update_performance_cycle_returns_updated_resource(): void
    {
        $this->findPerformanceCycleService->method('find')->willReturn($this->buildPerformanceCycle(id: 11));
        $this->updatePerformanceCycleService->expects($this->once())->method('execute')->willReturn($this->buildPerformanceCycle(id: 11));

        $this->withHeader('X-Tenant-ID', '7')
            ->putJson('/api/hr/performance-cycles/11', ['name' => 'Updated Q2 Cycle'])
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 11);
    }

    public function test_update_performance_cycle_returns_404_when_not_found(): void
    {
        $this->findPerformanceCycleService->method('find')->willReturn(null);
        $this->updatePerformanceCycleService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->putJson('/api/hr/performance-cycles/9999', ['name' => 'Ghost'])
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    public function test_delete_performance_cycle_returns_204(): void
    {
        $this->findPerformanceCycleService->method('find')->willReturn($this->buildPerformanceCycle(id: 11));

        $this->withHeader('X-Tenant-ID', '7')
            ->deleteJson('/api/hr/performance-cycles/11')
            ->assertStatus(HttpResponse::HTTP_NO_CONTENT);
    }

    public function test_delete_performance_cycle_returns_404_when_not_found(): void
    {
        $this->findPerformanceCycleService->method('find')->willReturn(null);

        $this->withHeader('X-Tenant-ID', '7')
            ->deleteJson('/api/hr/performance-cycles/9999')
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    // — PerformanceReview update / delete —

    public function test_update_performance_review_returns_updated_resource(): void
    {
        $this->findPerformanceReviewService->method('find')->willReturn($this->buildPerformanceReview(id: 40));
        $this->updatePerformanceReviewService->expects($this->once())->method('execute')->willReturn($this->buildPerformanceReview(id: 40));

        $this->withHeader('X-Tenant-ID', '7')
            ->putJson('/api/hr/performance-reviews/40', ['strengths' => 'Leadership'])
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 40);
    }

    public function test_update_performance_review_returns_404_when_not_found(): void
    {
        $this->findPerformanceReviewService->method('find')->willReturn(null);
        $this->updatePerformanceReviewService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->putJson('/api/hr/performance-reviews/9999', ['strengths' => 'Ghost'])
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    public function test_delete_performance_review_returns_204(): void
    {
        $this->findPerformanceReviewService->method('find')->willReturn($this->buildPerformanceReview(id: 40));

        $this->withHeader('X-Tenant-ID', '7')
            ->deleteJson('/api/hr/performance-reviews/40')
            ->assertStatus(HttpResponse::HTTP_NO_CONTENT);
    }

    public function test_delete_performance_review_returns_404_when_not_found(): void
    {
        $this->findPerformanceReviewService->method('find')->willReturn(null);

        $this->withHeader('X-Tenant-ID', '7')
            ->deleteJson('/api/hr/performance-reviews/9999')
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    // — EmployeeDocument update / delete —

    public function test_update_employee_document_returns_updated_resource(): void
    {
        $this->findEmployeeDocumentService->method('find')->willReturn($this->buildEmployeeDocument(id: 9));
        $this->storeEmployeeDocumentService->expects($this->once())->method('execute')->willReturn($this->buildEmployeeDocument(id: 9));

        $this->withHeader('X-Tenant-ID', '7')
            ->putJson('/api/hr/employee-documents/9', [
                'tenant_id' => 7,
                'employee_id' => 101,
                'document_type' => 'contract',
                'title' => 'Updated Contract',
                'file_path' => 'documents/contract_v2.pdf',
            ])
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 9);
    }

    public function test_update_employee_document_returns_404_when_not_found(): void
    {
        $this->findEmployeeDocumentService->method('find')->willReturn(null);
        $this->storeEmployeeDocumentService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->putJson('/api/hr/employee-documents/9999', [
                'tenant_id' => 7,
                'employee_id' => 101,
                'document_type' => 'contract',
                'title' => 'Ghost',
                'file_path' => 'ghost.pdf',
            ])
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    public function test_delete_employee_document_returns_204(): void
    {
        $this->findEmployeeDocumentService->method('find')->willReturn($this->buildEmployeeDocument(id: 9));
        $this->deleteEmployeeDocumentService->expects($this->once())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->deleteJson('/api/hr/employee-documents/9')
            ->assertStatus(HttpResponse::HTTP_NO_CONTENT);
    }

    public function test_delete_employee_document_returns_404_when_not_found(): void
    {
        $this->findEmployeeDocumentService->method('find')->willReturn(null);
        $this->deleteEmployeeDocumentService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '7')
            ->deleteJson('/api/hr/employee-documents/9999')
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    // =========================================================================
    // Additional entity builders
    // =========================================================================

    private function buildAttendanceLog(int $id): AttendanceLog
    {
        $now = new \DateTimeImmutable;

        return new AttendanceLog(
            tenantId: 7,
            employeeId: 101,
            biometricDeviceId: null,
            punchTime: new \DateTimeImmutable('2026-06-15 08:00:00'),
            punchType: 'in',
            source: 'manual',
            rawData: [],
            processedAt: null,
            createdAt: $now,
            updatedAt: $now,
            id: $id,
        );
    }

    private function buildLeaveBalance(int $id): LeaveBalance
    {
        $now = new \DateTimeImmutable;

        return new LeaveBalance(
            tenantId: 7,
            employeeId: 101,
            leaveTypeId: 10,
            year: 2026,
            allocated: 21.0,
            used: 5.0,
            pending: 0.0,
            carried: 2.0,
            createdAt: $now,
            updatedAt: $now,
            id: $id,
        );
    }

    private function buildLeavePolicy(int $id): LeavePolicy
    {
        $now = new \DateTimeImmutable;

        return new LeavePolicy(
            tenantId: 7,
            leaveTypeId: 10,
            name: 'Standard Annual Leave Policy',
            accrualType: 'monthly',
            accrualAmount: 1.75,
            orgUnitId: null,
            isActive: true,
            metadata: [],
            createdAt: $now,
            updatedAt: $now,
            id: $id,
        );
    }

    private function buildPayrollItem(int $id): PayrollItem
    {
        $now = new \DateTimeImmutable;

        return new PayrollItem(
            tenantId: 7,
            name: 'Basic Salary',
            code: 'BASIC',
            type: 'earning',
            calculationType: 'fixed',
            value: '50000.000000',
            isActive: true,
            isTaxable: true,
            accountId: null,
            metadata: [],
            createdAt: $now,
            updatedAt: $now,
            id: $id,
        );
    }

    private function buildPayslip(int $id): Payslip
    {
        $now = new \DateTimeImmutable;

        return new Payslip(
            tenantId: 7,
            employeeId: 101,
            payrollRunId: 30,
            periodStart: new \DateTimeImmutable('2026-05-01'),
            periodEnd: new \DateTimeImmutable('2026-05-31'),
            grossSalary: '50000.000000',
            totalDeductions: '5000.000000',
            netSalary: '45000.000000',
            baseSalary: '50000.000000',
            workedDays: 22.0,
            status: 'generated',
            journalEntryId: null,
            metadata: [],
            createdAt: $now,
            updatedAt: $now,
            id: $id,
        );
    }

    private function buildPerformanceCycle(int $id): PerformanceCycle
    {
        $now = new \DateTimeImmutable;

        return new PerformanceCycle(
            tenantId: 7,
            name: 'Q2 2026 Performance Cycle',
            periodStart: new \DateTimeImmutable('2026-04-01'),
            periodEnd: new \DateTimeImmutable('2026-06-30'),
            isActive: true,
            metadata: [],
            createdAt: $now,
            updatedAt: $now,
            id: $id,
        );
    }

    private function buildEmployeeDocument(int $id): EmployeeDocument
    {
        $now = new \DateTimeImmutable;

        return new EmployeeDocument(
            tenantId: 7,
            employeeId: 101,
            documentType: 'contract',
            title: 'Employment Contract',
            description: 'Standard employment contract',
            filePath: 'documents/contract.pdf',
            mimeType: 'application/pdf',
            fileSize: 102400,
            issuedDate: new \DateTimeImmutable('2026-01-01'),
            expiryDate: null,
            metadata: [],
            createdAt: $now,
            updatedAt: $now,
            id: $id,
        );
    }

    private function buildAttendanceRecord(int $id): AttendanceRecord
    {
        $now = new \DateTimeImmutable;

        return new AttendanceRecord(
            tenantId: 7,
            employeeId: 101,
            attendanceDate: new \DateTimeImmutable('2026-04-24'),
            checkIn: new \DateTimeImmutable('2026-04-24 08:00:00'),
            checkOut: new \DateTimeImmutable('2026-04-24 17:00:00'),
            breakDuration: 60,
            workedMinutes: 480,
            overtimeMinutes: 0,
            status: AttendanceStatus::PRESENT,
            shiftId: 1,
            remarks: '',
            metadata: [],
            createdAt: $now,
            updatedAt: $now,
            id: $id,
        );
    }

    private function clearRoutesCacheOnce(): void
    {
        if (self::$routesCleared) {
            return;
        }

        Artisan::call('route:clear');
        self::$routesCleared = true;
    }
}
