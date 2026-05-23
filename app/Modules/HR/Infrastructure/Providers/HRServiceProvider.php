<?php

namespace Modules\HR\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\HR\Application\Repositories\AttendanceLogRepositoryInterface;
use Modules\HR\Application\Repositories\AttendanceRecordRepositoryInterface;
use Modules\HR\Application\Repositories\BiometricDeviceRepositoryInterface;
use Modules\HR\Application\Repositories\DepartmentRepositoryInterface;
use Modules\HR\Application\Repositories\DesignationRepositoryInterface;
use Modules\HR\Application\Repositories\EmployeeContactRepositoryInterface;
use Modules\HR\Application\Repositories\EmployeeContractRepositoryInterface;
use Modules\HR\Application\Repositories\EmployeeDocumentRepositoryInterface;
use Modules\HR\Application\Repositories\EmployeeRepositoryInterface;
use Modules\HR\Application\Repositories\EmployeeSalaryAssignmentRepositoryInterface;
use Modules\HR\Application\Repositories\EmploymentTypeRepositoryInterface;
use Modules\HR\Application\Repositories\HolidayRepositoryInterface;
use Modules\HR\Application\Repositories\LeaveAllocationRepositoryInterface;
use Modules\HR\Application\Repositories\LeaveApplicationRepositoryInterface;
use Modules\HR\Application\Repositories\LeavePolicyLineRepositoryInterface;
use Modules\HR\Application\Repositories\LeavePolicyRepositoryInterface;
use Modules\HR\Application\Repositories\LeaveTypeRepositoryInterface;
use Modules\HR\Application\Repositories\PayrollRunRepositoryInterface;
use Modules\HR\Application\Repositories\PayslipLineRepositoryInterface;
use Modules\HR\Application\Repositories\PayslipRepositoryInterface;
use Modules\HR\Application\Repositories\PerformanceCycleRepositoryInterface;
use Modules\HR\Application\Repositories\PerformanceReviewRepositoryInterface;
use Modules\HR\Application\Repositories\SalaryComponentRepositoryInterface;
use Modules\HR\Application\Repositories\SalaryStructureLineRepositoryInterface;
use Modules\HR\Application\Repositories\SalaryStructureRepositoryInterface;
use Modules\HR\Application\Repositories\ShiftAssignmentRepositoryInterface;
use Modules\HR\Application\Repositories\ShiftRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentAttendanceLogRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentAttendanceRecordRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentBiometricDeviceRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentDepartmentRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentDesignationRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentEmployeeContactRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentEmployeeContractRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentEmployeeDocumentRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentEmployeeRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentEmployeeSalaryAssignmentRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentEmploymentTypeRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentHolidayRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentLeaveAllocationRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentLeaveApplicationRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentLeavePolicyLineRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentLeavePolicyRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentLeaveTypeRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentPayrollRunRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentPayslipLineRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentPayslipRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentPerformanceCycleRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentPerformanceReviewRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentSalaryComponentRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentSalaryStructureLineRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentSalaryStructureRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentShiftAssignmentRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentShiftRepository;

class HRServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach ([
            AttendanceLogRepositoryInterface::class => EloquentAttendanceLogRepository::class,
            AttendanceRecordRepositoryInterface::class => EloquentAttendanceRecordRepository::class,
            BiometricDeviceRepositoryInterface::class => EloquentBiometricDeviceRepository::class,
            DepartmentRepositoryInterface::class => EloquentDepartmentRepository::class,
            DesignationRepositoryInterface::class => EloquentDesignationRepository::class,
            EmployeeContactRepositoryInterface::class => EloquentEmployeeContactRepository::class,
            EmployeeContractRepositoryInterface::class => EloquentEmployeeContractRepository::class,
            EmployeeDocumentRepositoryInterface::class => EloquentEmployeeDocumentRepository::class,
            EmployeeRepositoryInterface::class => EloquentEmployeeRepository::class,
            EmployeeSalaryAssignmentRepositoryInterface::class => EloquentEmployeeSalaryAssignmentRepository::class,
            EmploymentTypeRepositoryInterface::class => EloquentEmploymentTypeRepository::class,
            HolidayRepositoryInterface::class => EloquentHolidayRepository::class,
            LeaveAllocationRepositoryInterface::class => EloquentLeaveAllocationRepository::class,
            LeaveApplicationRepositoryInterface::class => EloquentLeaveApplicationRepository::class,
            LeavePolicyLineRepositoryInterface::class => EloquentLeavePolicyLineRepository::class,
            LeavePolicyRepositoryInterface::class => EloquentLeavePolicyRepository::class,
            LeaveTypeRepositoryInterface::class => EloquentLeaveTypeRepository::class,
            PayrollRunRepositoryInterface::class => EloquentPayrollRunRepository::class,
            PayslipLineRepositoryInterface::class => EloquentPayslipLineRepository::class,
            PayslipRepositoryInterface::class => EloquentPayslipRepository::class,
            PerformanceCycleRepositoryInterface::class => EloquentPerformanceCycleRepository::class,
            PerformanceReviewRepositoryInterface::class => EloquentPerformanceReviewRepository::class,
            SalaryComponentRepositoryInterface::class => EloquentSalaryComponentRepository::class,
            SalaryStructureLineRepositoryInterface::class => EloquentSalaryStructureLineRepository::class,
            SalaryStructureRepositoryInterface::class => EloquentSalaryStructureRepository::class,
            ShiftAssignmentRepositoryInterface::class => EloquentShiftAssignmentRepository::class,
            ShiftRepositoryInterface::class => EloquentShiftRepository::class,
        ] as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../Infrastructure/Persistence/Eloquent/Migrations');
    }
}
