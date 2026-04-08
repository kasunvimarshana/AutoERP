<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\HR\Application\Biometric\BiometricAttendanceServiceInterface;
use Modules\HR\Application\Biometric\BiometricDeviceRegistryInterface;
use Modules\HR\Application\Biometric\BiometricEnrollmentServiceInterface;
use Modules\HR\Application\Contracts\ApproveLeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\CancelLeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\CreateAttendanceServiceInterface;
use Modules\HR\Application\Contracts\CreateDepartmentServiceInterface;
use Modules\HR\Application\Contracts\CreateEmployeeServiceInterface;
use Modules\HR\Application\Contracts\CreateLeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\CreatePayrollServiceInterface;
use Modules\HR\Application\Contracts\CreatePerformanceReviewServiceInterface;
use Modules\HR\Application\Contracts\CreatePositionServiceInterface;
use Modules\HR\Application\Contracts\CreateTrainingServiceInterface;
use Modules\HR\Application\Contracts\DeleteAttendanceServiceInterface;
use Modules\HR\Application\Contracts\DeleteDepartmentServiceInterface;
use Modules\HR\Application\Contracts\DeleteEmployeeServiceInterface;
use Modules\HR\Application\Contracts\DeleteLeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\DeletePayrollServiceInterface;
use Modules\HR\Application\Contracts\DeletePerformanceReviewServiceInterface;
use Modules\HR\Application\Contracts\DeletePositionServiceInterface;
use Modules\HR\Application\Contracts\DeleteTrainingServiceInterface;
use Modules\HR\Application\Contracts\FindAttendanceServiceInterface;
use Modules\HR\Application\Contracts\FindDepartmentServiceInterface;
use Modules\HR\Application\Contracts\FindEmployeeServiceInterface;
use Modules\HR\Application\Contracts\FindLeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\FindPayrollServiceInterface;
use Modules\HR\Application\Contracts\FindPerformanceReviewServiceInterface;
use Modules\HR\Application\Contracts\FindPositionServiceInterface;
use Modules\HR\Application\Contracts\FindTrainingServiceInterface;
use Modules\HR\Application\Contracts\LinkEmployeeToUserServiceInterface;
use Modules\HR\Application\Contracts\ProcessPayrollServiceInterface;
use Modules\HR\Application\Contracts\RejectLeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\SubmitPerformanceReviewServiceInterface;
use Modules\HR\Application\Contracts\UpdateAttendanceServiceInterface;
use Modules\HR\Application\Contracts\UpdateDepartmentServiceInterface;
use Modules\HR\Application\Contracts\UpdateEmployeeServiceInterface;
use Modules\HR\Application\Contracts\UpdateLeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\UpdatePayrollServiceInterface;
use Modules\HR\Application\Contracts\UpdatePerformanceReviewServiceInterface;
use Modules\HR\Application\Contracts\UpdatePositionServiceInterface;
use Modules\HR\Application\Contracts\UpdateTrainingServiceInterface;
use Modules\HR\Application\Services\ApproveLeaveRequestService;
use Modules\HR\Application\Services\CancelLeaveRequestService;
use Modules\HR\Application\Services\CreateAttendanceService;
use Modules\HR\Application\Services\CreateDepartmentService;
use Modules\HR\Application\Services\CreateEmployeeService;
use Modules\HR\Application\Services\CreateLeaveRequestService;
use Modules\HR\Application\Services\CreatePayrollService;
use Modules\HR\Application\Services\CreatePerformanceReviewService;
use Modules\HR\Application\Services\CreatePositionService;
use Modules\HR\Application\Services\CreateTrainingService;
use Modules\HR\Application\Services\DeleteAttendanceService;
use Modules\HR\Application\Services\DeleteDepartmentService;
use Modules\HR\Application\Services\DeleteEmployeeService;
use Modules\HR\Application\Services\DeleteLeaveRequestService;
use Modules\HR\Application\Services\DeletePayrollService;
use Modules\HR\Application\Services\DeletePerformanceReviewService;
use Modules\HR\Application\Services\DeletePositionService;
use Modules\HR\Application\Services\DeleteTrainingService;
use Modules\HR\Application\Services\FindAttendanceService;
use Modules\HR\Application\Services\FindDepartmentService;
use Modules\HR\Application\Services\FindEmployeeService;
use Modules\HR\Application\Services\FindLeaveRequestService;
use Modules\HR\Application\Services\FindPayrollService;
use Modules\HR\Application\Services\FindPerformanceReviewService;
use Modules\HR\Application\Services\FindPositionService;
use Modules\HR\Application\Services\FindTrainingService;
use Modules\HR\Application\Services\LinkEmployeeToUserService;
use Modules\HR\Application\Services\ProcessPayrollService;
use Modules\HR\Application\Services\RejectLeaveRequestService;
use Modules\HR\Application\Services\SubmitPerformanceReviewService;
use Modules\HR\Application\Services\UpdateAttendanceService;
use Modules\HR\Application\Services\UpdateDepartmentService;
use Modules\HR\Application\Services\UpdateEmployeeService;
use Modules\HR\Application\Services\UpdateLeaveRequestService;
use Modules\HR\Application\Services\UpdatePayrollService;
use Modules\HR\Application\Services\UpdatePerformanceReviewService;
use Modules\HR\Application\Services\UpdatePositionService;
use Modules\HR\Application\Services\UpdateTrainingService;
use Modules\HR\Domain\RepositoryInterfaces\DepartmentRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\AttendanceRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\EmployeeRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\LeaveRequestRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\PayrollRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\PerformanceReviewRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\PositionRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\TrainingRepositoryInterface;
use Modules\HR\Infrastructure\Biometric\BiometricAttendanceService;
use Modules\HR\Infrastructure\Biometric\BiometricDeviceRegistry;
use Modules\HR\Infrastructure\Biometric\BiometricEnrollmentService;
use Modules\HR\Infrastructure\Biometric\FingerprintDeviceAdapter;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\AttendanceModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\DepartmentModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\LeaveRequestModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PayrollModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PerformanceReviewModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PositionModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\TrainingModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentAttendanceRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentDepartmentRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentEmployeeRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentLeaveRequestRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentPayrollRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentPerformanceReviewRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentPositionRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentTrainingRepository;

class HRServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repositories
        $this->app->bind(EmployeeRepositoryInterface::class, function ($app) {
            return new EloquentEmployeeRepository($app->make(EmployeeModel::class));
        });
        $this->app->bind(DepartmentRepositoryInterface::class, function ($app) {
            return new EloquentDepartmentRepository($app->make(DepartmentModel::class));
        });
        $this->app->bind(PositionRepositoryInterface::class, function ($app) {
            return new EloquentPositionRepository($app->make(PositionModel::class));
        });
        $this->app->bind(LeaveRequestRepositoryInterface::class, function ($app) {
            return new EloquentLeaveRequestRepository($app->make(LeaveRequestModel::class));
        });
        $this->app->bind(AttendanceRepositoryInterface::class, function ($app) {
            return new EloquentAttendanceRepository($app->make(AttendanceModel::class));
        });

        // Employee Services
        $this->app->bind(FindEmployeeServiceInterface::class, function ($app) {
            return new FindEmployeeService($app->make(EmployeeRepositoryInterface::class));
        });
        $this->app->bind(CreateEmployeeServiceInterface::class, function ($app) {
            return new CreateEmployeeService($app->make(EmployeeRepositoryInterface::class));
        });
        $this->app->bind(UpdateEmployeeServiceInterface::class, function ($app) {
            return new UpdateEmployeeService($app->make(EmployeeRepositoryInterface::class));
        });
        $this->app->bind(DeleteEmployeeServiceInterface::class, function ($app) {
            return new DeleteEmployeeService($app->make(EmployeeRepositoryInterface::class));
        });
        $this->app->bind(LinkEmployeeToUserServiceInterface::class, function ($app) {
            return new LinkEmployeeToUserService($app->make(EmployeeRepositoryInterface::class));
        });

        // Department Services
        $this->app->bind(FindDepartmentServiceInterface::class, function ($app) {
            return new FindDepartmentService($app->make(DepartmentRepositoryInterface::class));
        });
        $this->app->bind(CreateDepartmentServiceInterface::class, function ($app) {
            return new CreateDepartmentService($app->make(DepartmentRepositoryInterface::class));
        });
        $this->app->bind(UpdateDepartmentServiceInterface::class, function ($app) {
            return new UpdateDepartmentService($app->make(DepartmentRepositoryInterface::class));
        });
        $this->app->bind(DeleteDepartmentServiceInterface::class, function ($app) {
            return new DeleteDepartmentService($app->make(DepartmentRepositoryInterface::class));
        });

        // Position Services
        $this->app->bind(FindPositionServiceInterface::class, function ($app) {
            return new FindPositionService($app->make(PositionRepositoryInterface::class));
        });
        $this->app->bind(CreatePositionServiceInterface::class, function ($app) {
            return new CreatePositionService($app->make(PositionRepositoryInterface::class));
        });
        $this->app->bind(UpdatePositionServiceInterface::class, function ($app) {
            return new UpdatePositionService($app->make(PositionRepositoryInterface::class));
        });
        $this->app->bind(DeletePositionServiceInterface::class, function ($app) {
            return new DeletePositionService($app->make(PositionRepositoryInterface::class));
        });

        // Leave Request Services
        $this->app->bind(FindLeaveRequestServiceInterface::class, function ($app) {
            return new FindLeaveRequestService($app->make(LeaveRequestRepositoryInterface::class));
        });
        $this->app->bind(CreateLeaveRequestServiceInterface::class, function ($app) {
            return new CreateLeaveRequestService($app->make(LeaveRequestRepositoryInterface::class));
        });
        $this->app->bind(UpdateLeaveRequestServiceInterface::class, function ($app) {
            return new UpdateLeaveRequestService($app->make(LeaveRequestRepositoryInterface::class));
        });
        $this->app->bind(DeleteLeaveRequestServiceInterface::class, function ($app) {
            return new DeleteLeaveRequestService($app->make(LeaveRequestRepositoryInterface::class));
        });
        $this->app->bind(ApproveLeaveRequestServiceInterface::class, function ($app) {
            return new ApproveLeaveRequestService($app->make(LeaveRequestRepositoryInterface::class));
        });
        $this->app->bind(RejectLeaveRequestServiceInterface::class, function ($app) {
            return new RejectLeaveRequestService($app->make(LeaveRequestRepositoryInterface::class));
        });
        $this->app->bind(CancelLeaveRequestServiceInterface::class, function ($app) {
            return new CancelLeaveRequestService($app->make(LeaveRequestRepositoryInterface::class));
        });

        // Attendance Services
        $this->app->bind(FindAttendanceServiceInterface::class, function ($app) {
            return new FindAttendanceService($app->make(AttendanceRepositoryInterface::class));
        });
        $this->app->bind(CreateAttendanceServiceInterface::class, function ($app) {
            return new CreateAttendanceService($app->make(AttendanceRepositoryInterface::class));
        });
        $this->app->bind(UpdateAttendanceServiceInterface::class, function ($app) {
            return new UpdateAttendanceService($app->make(AttendanceRepositoryInterface::class));
        });
        $this->app->bind(DeleteAttendanceServiceInterface::class, function ($app) {
            return new DeleteAttendanceService($app->make(AttendanceRepositoryInterface::class));
        });

        // Payroll Repository
        $this->app->bind(PayrollRepositoryInterface::class, function ($app) {
            return new EloquentPayrollRepository($app->make(PayrollModel::class));
        });

        // Payroll Services
        $this->app->bind(FindPayrollServiceInterface::class, function ($app) {
            return new FindPayrollService($app->make(PayrollRepositoryInterface::class));
        });
        $this->app->bind(CreatePayrollServiceInterface::class, function ($app) {
            return new CreatePayrollService($app->make(PayrollRepositoryInterface::class));
        });
        $this->app->bind(UpdatePayrollServiceInterface::class, function ($app) {
            return new UpdatePayrollService($app->make(PayrollRepositoryInterface::class));
        });
        $this->app->bind(DeletePayrollServiceInterface::class, function ($app) {
            return new DeletePayrollService($app->make(PayrollRepositoryInterface::class));
        });
        $this->app->bind(ProcessPayrollServiceInterface::class, function ($app) {
            return new ProcessPayrollService($app->make(PayrollRepositoryInterface::class));
        });

        // PerformanceReview Repository
        $this->app->bind(PerformanceReviewRepositoryInterface::class, function ($app) {
            return new EloquentPerformanceReviewRepository($app->make(PerformanceReviewModel::class));
        });

        // PerformanceReview Services
        $this->app->bind(FindPerformanceReviewServiceInterface::class, function ($app) {
            return new FindPerformanceReviewService($app->make(PerformanceReviewRepositoryInterface::class));
        });
        $this->app->bind(CreatePerformanceReviewServiceInterface::class, function ($app) {
            return new CreatePerformanceReviewService($app->make(PerformanceReviewRepositoryInterface::class));
        });
        $this->app->bind(UpdatePerformanceReviewServiceInterface::class, function ($app) {
            return new UpdatePerformanceReviewService($app->make(PerformanceReviewRepositoryInterface::class));
        });
        $this->app->bind(DeletePerformanceReviewServiceInterface::class, function ($app) {
            return new DeletePerformanceReviewService($app->make(PerformanceReviewRepositoryInterface::class));
        });
        $this->app->bind(SubmitPerformanceReviewServiceInterface::class, function ($app) {
            return new SubmitPerformanceReviewService($app->make(PerformanceReviewRepositoryInterface::class));
        });

        // Training Repository
        $this->app->bind(TrainingRepositoryInterface::class, function ($app) {
            return new EloquentTrainingRepository($app->make(TrainingModel::class));
        });

        // Training Services
        $this->app->bind(FindTrainingServiceInterface::class, function ($app) {
            return new FindTrainingService($app->make(TrainingRepositoryInterface::class));
        });
        $this->app->bind(CreateTrainingServiceInterface::class, function ($app) {
            return new CreateTrainingService($app->make(TrainingRepositoryInterface::class));
        });
        $this->app->bind(UpdateTrainingServiceInterface::class, function ($app) {
            return new UpdateTrainingService($app->make(TrainingRepositoryInterface::class));
        });
        $this->app->bind(DeleteTrainingServiceInterface::class, function ($app) {
            return new DeleteTrainingService($app->make(TrainingRepositoryInterface::class));
        });

        // Biometric Device Registry (singleton so all services share the same registry)
        $this->app->singleton(BiometricDeviceRegistryInterface::class, function () {
            return new BiometricDeviceRegistry;
        });

        // Biometric Attendance Service
        $this->app->bind(BiometricAttendanceServiceInterface::class, function ($app) {
            return new BiometricAttendanceService(
                $app->make(BiometricDeviceRegistryInterface::class),
                $app->make(AttendanceRepositoryInterface::class),
            );
        });

        // Biometric Enrollment Service
        $this->app->bind(BiometricEnrollmentServiceInterface::class, function ($app) {
            return new BiometricEnrollmentService(
                $app->make(BiometricDeviceRegistryInterface::class),
            );
        });
    }

    public function boot(): void
    {
        Route::middleware('api')
             ->prefix('api')
             ->group(function () {
                 $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
             });

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Register the default fingerprint device so it is available out of the box.
        // Production deployments should override this in their own service provider
        // or config file, pointing to real hardware device IDs.
        /** @var BiometricDeviceRegistry $registry */
        $registry = $this->app->make(BiometricDeviceRegistryInterface::class);
        $registry->register(new FingerprintDeviceAdapter('default-fingerprint'));
    }
}
