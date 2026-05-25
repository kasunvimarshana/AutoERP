<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\CountryModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasReferenceScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasStatusScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\AttendanceLogModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\AttendanceRecordModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\DepartmentModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\DesignationModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeContactModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeContractModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeDocumentModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeSalaryAssignmentModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmploymentTypeModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\LeaveAllocationModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\LeaveApplicationModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PayslipModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PerformanceReviewModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\ShiftAssignmentModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLesseeRunningChartModel;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLessorRunningChartModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceLaborAssignmentModel;

class EmployeeModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope, HasReferenceScope, HasStatusScope, SoftDeletes;

    protected $table = 'employees';

    protected $guarded = ['id'];

    protected static string $referenceColumn = 'code';

    protected function casts(): array
    {
        return [
            'confirmation_date' => 'date',
            'hire_date' => 'date',
            'metadata' => 'array',
            'row_version' => 'integer',
            'termination_date' => 'date',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(CountryModel::class, 'country_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(DepartmentModel::class, 'department_id');
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(DesignationModel::class, 'designation_id');
    }

    public function employmentType(): BelongsTo
    {
        return $this->belongsTo(EmploymentTypeModel::class, 'employment_type_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLogModel::class, 'employee_id');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecordModel::class, 'employee_id');
    }

    public function employeeContacts(): HasMany
    {
        return $this->hasMany(EmployeeContactModel::class, 'employee_id');
    }

    public function employeeContracts(): HasMany
    {
        return $this->hasMany(EmployeeContractModel::class, 'employee_id');
    }

    public function employeeDocuments(): HasMany
    {
        return $this->hasMany(EmployeeDocumentModel::class, 'employee_id');
    }

    public function employeeSalaryAssignments(): HasMany
    {
        return $this->hasMany(EmployeeSalaryAssignmentModel::class, 'employee_id');
    }

    public function leaveAllocations(): HasMany
    {
        return $this->hasMany(LeaveAllocationModel::class, 'employee_id');
    }

    public function leaveApplications(): HasMany
    {
        return $this->hasMany(LeaveApplicationModel::class, 'employee_id');
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(PayslipModel::class, 'employee_id');
    }

    public function performanceReviews(): HasMany
    {
        return $this->hasMany(PerformanceReviewModel::class, 'employee_id');
    }

    public function shiftAssignments(): HasMany
    {
        return $this->hasMany(ShiftAssignmentModel::class, 'employee_id');
    }

    public function vehicleRentalLesseeRunningCharts(): HasMany
    {
        return $this->hasMany(VehicleRentalLesseeRunningChartModel::class, 'driver_id');
    }

    public function vehicleRentalLessorRunningCharts(): HasMany
    {
        return $this->hasMany(VehicleRentalLessorRunningChartModel::class, 'driver_id');
    }

    public function vehicleServiceLaborAssignments(): HasMany
    {
        return $this->hasMany(VehicleServiceLaborAssignmentModel::class, 'employee_id');
    }

}
