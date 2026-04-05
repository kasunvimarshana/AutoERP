<?php
declare(strict_types=1);
namespace Modules\HR\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\HR\Application\Contracts\AttendanceServiceInterface;
use Modules\HR\Application\Contracts\DepartmentServiceInterface;
use Modules\HR\Application\Contracts\EmployeeServiceInterface;
use Modules\HR\Application\Contracts\LeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\LeaveTypeServiceInterface;
use Modules\HR\Application\Contracts\PayrollServiceInterface;
use Modules\HR\Application\Contracts\PositionServiceInterface;
use Modules\HR\Application\Services\AttendanceService;
use Modules\HR\Application\Services\DepartmentService;
use Modules\HR\Application\Services\EmployeeService;
use Modules\HR\Application\Services\LeaveRequestService;
use Modules\HR\Application\Services\LeaveTypeService;
use Modules\HR\Application\Services\PayrollService;
use Modules\HR\Application\Services\PositionService;
use Modules\HR\Domain\RepositoryInterfaces\AttendanceRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\DepartmentRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\EmployeeRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\LeaveRequestRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\LeaveTypeRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\PayrollRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\PositionRepositoryInterface;
use Modules\HR\Infrastructure\Biometric\BiometricDeviceManager;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\AttendanceModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\DepartmentModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\LeaveRequestModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\LeaveTypeModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PayrollModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PositionModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentAttendanceRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentDepartmentRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentEmployeeRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentLeaveRequestRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentLeaveTypeRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentPayrollRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentPositionRepository;

class HRServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ── Biometric Device Manager ──────────────────────────────────────────
        $this->app->singleton(BiometricDeviceManager::class, fn($app) =>
            new BiometricDeviceManager($app['config']->get('hr.biometric', []))
        );

        // ── Repository Bindings ────────────────────────────────────────────────
        $this->app->bind(DepartmentRepositoryInterface::class, fn($app) =>
            new EloquentDepartmentRepository($app->make(DepartmentModel::class))
        );
        $this->app->bind(PositionRepositoryInterface::class, fn($app) =>
            new EloquentPositionRepository($app->make(PositionModel::class))
        );
        $this->app->bind(EmployeeRepositoryInterface::class, fn($app) =>
            new EloquentEmployeeRepository($app->make(EmployeeModel::class))
        );
        $this->app->bind(LeaveTypeRepositoryInterface::class, fn($app) =>
            new EloquentLeaveTypeRepository($app->make(LeaveTypeModel::class))
        );
        $this->app->bind(LeaveRequestRepositoryInterface::class, fn($app) =>
            new EloquentLeaveRequestRepository($app->make(LeaveRequestModel::class))
        );
        $this->app->bind(AttendanceRepositoryInterface::class, fn($app) =>
            new EloquentAttendanceRepository($app->make(AttendanceModel::class))
        );
        $this->app->bind(PayrollRepositoryInterface::class, fn($app) =>
            new EloquentPayrollRepository($app->make(PayrollModel::class))
        );

        // ── Service Bindings ───────────────────────────────────────────────────
        $this->app->bind(DepartmentServiceInterface::class, fn($app) =>
            new DepartmentService($app->make(DepartmentRepositoryInterface::class))
        );
        $this->app->bind(PositionServiceInterface::class, fn($app) =>
            new PositionService($app->make(PositionRepositoryInterface::class))
        );
        $this->app->bind(EmployeeServiceInterface::class, fn($app) =>
            new EmployeeService($app->make(EmployeeRepositoryInterface::class))
        );
        $this->app->bind(LeaveTypeServiceInterface::class, fn($app) =>
            new LeaveTypeService($app->make(LeaveTypeRepositoryInterface::class))
        );
        $this->app->bind(LeaveRequestServiceInterface::class, fn($app) =>
            new LeaveRequestService($app->make(LeaveRequestRepositoryInterface::class))
        );
        $this->app->bind(AttendanceServiceInterface::class, fn($app) =>
            new AttendanceService(
                $app->make(AttendanceRepositoryInterface::class),
                $app->make(EmployeeRepositoryInterface::class),
                $app->make(BiometricDeviceManager::class),
            )
        );
        $this->app->bind(PayrollServiceInterface::class, fn($app) =>
            new PayrollService(
                $app->make(PayrollRepositoryInterface::class),
                $app->make(EmployeeRepositoryInterface::class),
            )
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
