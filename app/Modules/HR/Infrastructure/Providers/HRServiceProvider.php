<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\HR\Application\Contracts\AllocateLeaveBalanceServiceInterface;
use Modules\HR\Application\Contracts\ApproveLeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\ApprovePayrollRunServiceInterface;
use Modules\HR\Application\Contracts\AssignShiftServiceInterface;
use Modules\HR\Application\Contracts\CancelLeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\CreateAttendanceLogServiceInterface;
use Modules\HR\Application\Contracts\CreateBiometricDeviceServiceInterface;
use Modules\HR\Application\Contracts\CreateLeavePolicyServiceInterface;
use Modules\HR\Application\Contracts\CreateLeaveTypeServiceInterface;
use Modules\HR\Application\Contracts\CreatePayrollItemServiceInterface;
use Modules\HR\Application\Contracts\CreatePayrollRunServiceInterface;
use Modules\HR\Application\Contracts\CreatePerformanceCycleServiceInterface;
use Modules\HR\Application\Contracts\CreatePerformanceReviewServiceInterface;
use Modules\HR\Application\Contracts\CreateShiftServiceInterface;
use Modules\HR\Application\Contracts\DeleteBiometricDeviceServiceInterface;
use Modules\HR\Application\Contracts\DeleteEmployeeDocumentServiceInterface;
use Modules\HR\Application\Contracts\DeleteLeaveTypeServiceInterface;
use Modules\HR\Application\Contracts\DeleteShiftServiceInterface;
use Modules\HR\Application\Contracts\FindAttendanceLogServiceInterface;
use Modules\HR\Application\Contracts\FindAttendanceRecordServiceInterface;
use Modules\HR\Application\Contracts\FindBiometricDeviceServiceInterface;
use Modules\HR\Application\Contracts\FindEmployeeDocumentServiceInterface;
use Modules\HR\Application\Contracts\FindLeaveBalanceServiceInterface;
use Modules\HR\Application\Contracts\FindLeavePolicyServiceInterface;
use Modules\HR\Application\Contracts\FindLeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\FindLeaveTypeServiceInterface;
use Modules\HR\Application\Contracts\FindPayrollItemServiceInterface;
use Modules\HR\Application\Contracts\FindPayrollRunServiceInterface;
use Modules\HR\Application\Contracts\FindPayslipServiceInterface;
use Modules\HR\Application\Contracts\FindPerformanceCycleServiceInterface;
use Modules\HR\Application\Contracts\FindPerformanceReviewServiceInterface;
use Modules\HR\Application\Contracts\FindShiftAssignmentServiceInterface;
use Modules\HR\Application\Contracts\FindShiftServiceInterface;
use Modules\HR\Application\Contracts\ProcessAttendanceServiceInterface;
use Modules\HR\Application\Contracts\ProcessPayrollRunServiceInterface;
use Modules\HR\Application\Contracts\RejectLeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\StoreEmployeeDocumentServiceInterface;
use Modules\HR\Application\Contracts\SubmitLeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\SubmitPerformanceReviewServiceInterface;
use Modules\HR\Application\Contracts\SyncBiometricDeviceServiceInterface;
use Modules\HR\Application\Contracts\UpdateAttendanceRecordServiceInterface;
use Modules\HR\Application\Contracts\UpdateBiometricDeviceServiceInterface;
use Modules\HR\Application\Contracts\UpdateLeavePolicyServiceInterface;
use Modules\HR\Application\Contracts\UpdateLeaveTypeServiceInterface;
use Modules\HR\Application\Contracts\UpdatePayrollItemServiceInterface;
use Modules\HR\Application\Contracts\UpdatePerformanceCycleServiceInterface;
use Modules\HR\Application\Contracts\UpdatePerformanceReviewServiceInterface;
use Modules\HR\Application\Contracts\UpdateShiftServiceInterface;
use Modules\HR\Application\Services\AllocateLeaveBalanceService;
use Modules\HR\Application\Services\ApproveLeaveRequestService;
use Modules\HR\Application\Services\ApprovePayrollRunService;
use Modules\HR\Application\Services\AssignShiftService;
use Modules\HR\Application\Services\CancelLeaveRequestService;
use Modules\HR\Application\Services\CreateAttendanceLogService;
use Modules\HR\Application\Services\CreateBiometricDeviceService;
use Modules\HR\Application\Services\CreateLeavePolicyService;
use Modules\HR\Application\Services\CreateLeaveTypeService;
use Modules\HR\Application\Services\CreatePayrollItemService;
use Modules\HR\Application\Services\CreatePayrollRunService;
use Modules\HR\Application\Services\CreatePerformanceCycleService;
use Modules\HR\Application\Services\CreatePerformanceReviewService;
use Modules\HR\Application\Services\CreateShiftService;
use Modules\HR\Application\Services\DeleteBiometricDeviceService;
use Modules\HR\Application\Services\DeleteEmployeeDocumentService;
use Modules\HR\Application\Services\DeleteLeaveTypeService;
use Modules\HR\Application\Services\DeleteShiftService;
use Modules\HR\Application\Services\FindAttendanceLogService;
use Modules\HR\Application\Services\FindAttendanceRecordService;
use Modules\HR\Application\Services\FindBiometricDeviceService;
use Modules\HR\Application\Services\FindEmployeeDocumentService;
use Modules\HR\Application\Services\FindLeaveBalanceService;
use Modules\HR\Application\Services\FindLeavePolicyService;
use Modules\HR\Application\Services\FindLeaveRequestService;
use Modules\HR\Application\Services\FindLeaveTypeService;
use Modules\HR\Application\Services\FindPayrollItemService;
use Modules\HR\Application\Services\FindPayrollRunService;
use Modules\HR\Application\Services\FindPayslipService;
use Modules\HR\Application\Services\FindPerformanceCycleService;
use Modules\HR\Application\Services\FindPerformanceReviewService;
use Modules\HR\Application\Services\FindShiftAssignmentService;
use Modules\HR\Application\Services\FindShiftService;
use Modules\HR\Application\Services\ProcessAttendanceService;
use Modules\HR\Application\Services\ProcessPayrollRunService;
use Modules\HR\Application\Services\RejectLeaveRequestService;
use Modules\HR\Application\Services\StoreEmployeeDocumentService;
use Modules\HR\Application\Services\SubmitLeaveRequestService;
use Modules\HR\Application\Services\SubmitPerformanceReviewService;
use Modules\HR\Application\Services\SyncBiometricDeviceService;
use Modules\HR\Application\Services\UpdateAttendanceRecordService;
use Modules\HR\Application\Services\UpdateBiometricDeviceService;
use Modules\HR\Application\Services\UpdateLeavePolicyService;
use Modules\HR\Application\Services\UpdateLeaveTypeService;
use Modules\HR\Application\Services\UpdatePayrollItemService;
use Modules\HR\Application\Services\UpdatePerformanceCycleService;
use Modules\HR\Application\Services\UpdatePerformanceReviewService;
use Modules\HR\Application\Services\UpdateShiftService;
use Modules\HR\Domain\Contracts\BiometricDeviceAdapterInterface;
use Modules\HR\Domain\RepositoryInterfaces\AttendanceLogRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\AttendanceRecordRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\BiometricDeviceRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\EmployeeDocumentRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\LeaveBalanceRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\LeavePolicyRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\LeaveRequestRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\LeaveTypeRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\PayrollItemRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\PayrollRunRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\PayslipRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\PerformanceCycleRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\PerformanceReviewRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\ShiftAssignmentRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\ShiftRepositoryInterface;
use Modules\HR\Infrastructure\Adapters\NullBiometricDeviceAdapter;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentAttendanceLogRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentAttendanceRecordRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentBiometricDeviceRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentEmployeeDocumentRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentLeaveBalanceRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentLeavePolicyRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentLeaveRequestRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentLeaveTypeRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentPayrollItemRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentPayrollRunRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentPayslipRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentPerformanceCycleRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentPerformanceReviewRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentShiftAssignmentRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentShiftRepository;

class HRServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $repositoryBindings = [
            ShiftRepositoryInterface::class => EloquentShiftRepository::class,
            ShiftAssignmentRepositoryInterface::class => EloquentShiftAssignmentRepository::class,
            LeaveTypeRepositoryInterface::class => EloquentLeaveTypeRepository::class,
            LeavePolicyRepositoryInterface::class => EloquentLeavePolicyRepository::class,
            LeaveBalanceRepositoryInterface::class => EloquentLeaveBalanceRepository::class,
            LeaveRequestRepositoryInterface::class => EloquentLeaveRequestRepository::class,
            AttendanceLogRepositoryInterface::class => EloquentAttendanceLogRepository::class,
            AttendanceRecordRepositoryInterface::class => EloquentAttendanceRecordRepository::class,
            BiometricDeviceRepositoryInterface::class => EloquentBiometricDeviceRepository::class,
            PayrollRunRepositoryInterface::class => EloquentPayrollRunRepository::class,
            PayrollItemRepositoryInterface::class => EloquentPayrollItemRepository::class,
            PayslipRepositoryInterface::class => EloquentPayslipRepository::class,
            PerformanceCycleRepositoryInterface::class => EloquentPerformanceCycleRepository::class,
            PerformanceReviewRepositoryInterface::class => EloquentPerformanceReviewRepository::class,
            EmployeeDocumentRepositoryInterface::class => EloquentEmployeeDocumentRepository::class,
        ];

        foreach ($repositoryBindings as $contract => $impl) {
            $this->app->bind($contract, $impl);
        }

        $serviceBindings = [
            CreateShiftServiceInterface::class => CreateShiftService::class,
            FindShiftServiceInterface::class => FindShiftService::class,
            UpdateShiftServiceInterface::class => UpdateShiftService::class,
            DeleteShiftServiceInterface::class => DeleteShiftService::class,
            AssignShiftServiceInterface::class => AssignShiftService::class,
            FindShiftAssignmentServiceInterface::class => FindShiftAssignmentService::class,
            CreateLeaveTypeServiceInterface::class => CreateLeaveTypeService::class,
            FindLeaveTypeServiceInterface::class => FindLeaveTypeService::class,
            UpdateLeaveTypeServiceInterface::class => UpdateLeaveTypeService::class,
            DeleteLeaveTypeServiceInterface::class => DeleteLeaveTypeService::class,
            CreateLeavePolicyServiceInterface::class => CreateLeavePolicyService::class,
            FindLeavePolicyServiceInterface::class => FindLeavePolicyService::class,
            UpdateLeavePolicyServiceInterface::class => UpdateLeavePolicyService::class,
            FindLeaveBalanceServiceInterface::class => FindLeaveBalanceService::class,
            AllocateLeaveBalanceServiceInterface::class => AllocateLeaveBalanceService::class,
            SubmitLeaveRequestServiceInterface::class => SubmitLeaveRequestService::class,
            FindLeaveRequestServiceInterface::class => FindLeaveRequestService::class,
            ApproveLeaveRequestServiceInterface::class => ApproveLeaveRequestService::class,
            RejectLeaveRequestServiceInterface::class => RejectLeaveRequestService::class,
            CancelLeaveRequestServiceInterface::class => CancelLeaveRequestService::class,
            CreateAttendanceLogServiceInterface::class => CreateAttendanceLogService::class,
            FindAttendanceLogServiceInterface::class => FindAttendanceLogService::class,
            FindAttendanceRecordServiceInterface::class => FindAttendanceRecordService::class,
            UpdateAttendanceRecordServiceInterface::class => UpdateAttendanceRecordService::class,
            ProcessAttendanceServiceInterface::class => ProcessAttendanceService::class,
            CreateBiometricDeviceServiceInterface::class => CreateBiometricDeviceService::class,
            FindBiometricDeviceServiceInterface::class => FindBiometricDeviceService::class,
            UpdateBiometricDeviceServiceInterface::class => UpdateBiometricDeviceService::class,
            DeleteBiometricDeviceServiceInterface::class => DeleteBiometricDeviceService::class,
            SyncBiometricDeviceServiceInterface::class => SyncBiometricDeviceService::class,
            CreatePayrollRunServiceInterface::class => CreatePayrollRunService::class,
            FindPayrollRunServiceInterface::class => FindPayrollRunService::class,
            ApprovePayrollRunServiceInterface::class => ApprovePayrollRunService::class,
            ProcessPayrollRunServiceInterface::class => ProcessPayrollRunService::class,
            CreatePayrollItemServiceInterface::class => CreatePayrollItemService::class,
            FindPayrollItemServiceInterface::class => FindPayrollItemService::class,
            UpdatePayrollItemServiceInterface::class => UpdatePayrollItemService::class,
            FindPayslipServiceInterface::class => FindPayslipService::class,
            CreatePerformanceCycleServiceInterface::class => CreatePerformanceCycleService::class,
            FindPerformanceCycleServiceInterface::class => FindPerformanceCycleService::class,
            UpdatePerformanceCycleServiceInterface::class => UpdatePerformanceCycleService::class,
            CreatePerformanceReviewServiceInterface::class => CreatePerformanceReviewService::class,
            FindPerformanceReviewServiceInterface::class => FindPerformanceReviewService::class,
            UpdatePerformanceReviewServiceInterface::class => UpdatePerformanceReviewService::class,
            SubmitPerformanceReviewServiceInterface::class => SubmitPerformanceReviewService::class,
            StoreEmployeeDocumentServiceInterface::class => StoreEmployeeDocumentService::class,
            FindEmployeeDocumentServiceInterface::class => FindEmployeeDocumentService::class,
            DeleteEmployeeDocumentServiceInterface::class => DeleteEmployeeDocumentService::class,
        ];

        foreach ($serviceBindings as $contract => $impl) {
            $this->app->bind($contract, $impl);
        }

        $this->app->bind(BiometricDeviceAdapterInterface::class, NullBiometricDeviceAdapter::class);
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__.'/../../routes/api.php',
            __DIR__.'/../../database/migrations',
        );
    }
}
