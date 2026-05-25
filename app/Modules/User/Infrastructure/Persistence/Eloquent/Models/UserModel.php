<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasStatusScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\BankReconciliationModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\LeaveApplicationModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PayrollRunModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PerformanceReviewModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\CycleCountHeaderModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\PickingTaskModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\PutAwayTaskModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\ReceiptInspectionModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockAdjustmentModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockMovementModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockTransferModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\TraceLogModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierModel;
use Modules\SystemUser\Infrastructure\Persistence\Eloquent\Models\SystemUserModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserDeviceModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserDocumentModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserPermissionModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserRoleModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserTenantModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceDiagnosticModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceInspectionModel;

class UserModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope, HasStatusScope, SoftDeletes;

    protected $table = 'users';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'email_verified_at' => 'datetime',
            'metadata' => 'array',
            'preferences' => 'array',
            'row_version' => 'integer',
        ];
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function bankReconciliations(): HasMany
    {
        return $this->hasMany(BankReconciliationModel::class, 'completed_by');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(CustomerModel::class, 'user_id');
    }

    public function cycleCountHeaders(): HasMany
    {
        return $this->hasMany(CycleCountHeaderModel::class, 'counted_by_user_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(EmployeeModel::class, 'user_id');
    }

    public function leaveApplications(): HasMany
    {
        return $this->hasMany(LeaveApplicationModel::class, 'approver_id');
    }

    public function payrollRuns(): HasMany
    {
        return $this->hasMany(PayrollRunModel::class, 'approved_by');
    }

    public function performanceReviews(): HasMany
    {
        return $this->hasMany(PerformanceReviewModel::class, 'reviewer_id');
    }

    public function pickingTasks(): HasMany
    {
        return $this->hasMany(PickingTaskModel::class, 'assigned_user_id');
    }

    public function putAwayTasks(): HasMany
    {
        return $this->hasMany(PutAwayTaskModel::class, 'assigned_user_id');
    }

    public function receiptInspections(): HasMany
    {
        return $this->hasMany(ReceiptInspectionModel::class, 'inspected_by');
    }

    public function stockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustmentModel::class, 'counted_by');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovementModel::class, 'performed_by');
    }

    public function stockTransfers(): HasMany
    {
        return $this->hasMany(StockTransferModel::class, 'requested_by');
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(SupplierModel::class, 'user_id');
    }

    public function systemUsers(): HasMany
    {
        return $this->hasMany(SystemUserModel::class, 'user_id');
    }

    public function traceLogs(): HasMany
    {
        return $this->hasMany(TraceLogModel::class, 'performed_by');
    }

    public function userDevices(): HasMany
    {
        return $this->hasMany(UserDeviceModel::class, 'user_id');
    }

    public function userDocuments(): HasMany
    {
        return $this->hasMany(UserDocumentModel::class, 'user_id');
    }

    public function userPermissions(): HasMany
    {
        return $this->hasMany(UserPermissionModel::class, 'user_id');
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRoleModel::class, 'user_id');
    }

    public function userTenants(): HasMany
    {
        return $this->hasMany(UserTenantModel::class, 'user_id');
    }

    public function vehicleServiceDiagnostics(): HasMany
    {
        return $this->hasMany(VehicleServiceDiagnosticModel::class, 'performed_by');
    }

    public function vehicleServiceInspections(): HasMany
    {
        return $this->hasMany(VehicleServiceInspectionModel::class, 'performed_by');
    }

}
