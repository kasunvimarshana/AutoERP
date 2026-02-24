<?php

namespace Modules\HR\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\HR\Application\Commands\ProcessPayrollRunsCommand;
use Modules\HR\Application\Listeners\HandleApplicantHiredListener;
use Modules\HR\Application\Listeners\HandleLeaveRequestApprovedListener;
use Modules\HR\Domain\Contracts\AttendanceRecordRepositoryInterface;
use Modules\HR\Domain\Contracts\DepartmentRepositoryInterface;
use Modules\HR\Domain\Contracts\EmployeeRepositoryInterface;
use Modules\HR\Domain\Contracts\PayrollRunRepositoryInterface;
use Modules\HR\Domain\Contracts\PayslipRepositoryInterface;
use Modules\HR\Domain\Contracts\PerformanceGoalRepositoryInterface;
use Modules\HR\Domain\Contracts\SalaryComponentRepositoryInterface;
use Modules\HR\Domain\Contracts\SalaryStructureAssignmentRepositoryInterface;
use Modules\HR\Domain\Contracts\SalaryStructureRepositoryInterface;
use Modules\HR\Infrastructure\Repositories\AttendanceRecordRepository;
use Modules\HR\Infrastructure\Repositories\DepartmentRepository;
use Modules\HR\Infrastructure\Repositories\EmployeeRepository;
use Modules\HR\Infrastructure\Repositories\PayrollRunRepository;
use Modules\HR\Infrastructure\Repositories\PayslipRepository;
use Modules\HR\Infrastructure\Repositories\PerformanceGoalRepository;
use Modules\HR\Infrastructure\Repositories\SalaryComponentRepository;
use Modules\HR\Infrastructure\Repositories\SalaryStructureAssignmentRepository;
use Modules\HR\Infrastructure\Repositories\SalaryStructureRepository;
use Modules\Recruitment\Domain\Events\ApplicantHired;
use Modules\Leave\Domain\Events\LeaveRequestApproved;

class HRServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EmployeeRepositoryInterface::class, EmployeeRepository::class);
        $this->app->bind(DepartmentRepositoryInterface::class, DepartmentRepository::class);
        $this->app->bind(PayrollRunRepositoryInterface::class, PayrollRunRepository::class);
        $this->app->bind(PayslipRepositoryInterface::class, PayslipRepository::class);
        $this->app->bind(AttendanceRecordRepositoryInterface::class, AttendanceRecordRepository::class);
        $this->app->bind(PerformanceGoalRepositoryInterface::class, PerformanceGoalRepository::class);
        $this->app->bind(SalaryComponentRepositoryInterface::class, SalaryComponentRepository::class);
        $this->app->bind(SalaryStructureRepositoryInterface::class, SalaryStructureRepository::class);
        $this->app->bind(SalaryStructureAssignmentRepositoryInterface::class, SalaryStructureAssignmentRepository::class);
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'hr');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');

        Event::listen(ApplicantHired::class, HandleApplicantHiredListener::class);
        Event::listen(LeaveRequestApproved::class, HandleLeaveRequestApprovedListener::class);

        if ($this->app->runningInConsole()) {
            $this->commands([ProcessPayrollRunsCommand::class]);
        }
    }
}
