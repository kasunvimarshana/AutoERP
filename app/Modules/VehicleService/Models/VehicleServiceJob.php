<?php

declare(strict_types=1);

namespace Modules\VehicleService\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Customer\Models\Customer;
use Modules\Hr\Models\HrEmployee;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleService\Enums\VehicleServiceCommissionType;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;
use Modules\VehicleService\Models\Concerns\HasVehicleServiceJobRowVersion;

final class VehicleServiceJob extends TenantOwnedModel
{
    use HasVehicleServiceJobRowVersion;
    use SoftDeletes;

    protected $table = 'vehicle_service_jobs';

    protected $fillable = [
        'tenant_id',
        'organization_unit_id',
        'job_number',
        'job_date',
        'expected_delivery_date',
        'customer_id',
        'bill_to_customer_id',
        'vehicle_id',
        'supervisor_employee_id',
        'supervisor_commission_type',
        'supervisor_commission_value',
        'supervisor_commission_amount',
        'status',
        'odometer_reading',
        'fuel_level',
        'priority',
        'notes',
        'subtotal',
        'discount_total',
        'tax_total',
        'charge_total',
        'grand_total',
        'approved_at',
        'completed_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'job_date' => 'date',
            'expected_delivery_date' => 'date',
            'customer_id' => 'integer',
            'bill_to_customer_id' => 'integer',
            'vehicle_id' => 'integer',
            'supervisor_employee_id' => 'integer',
            'supervisor_commission_type' => VehicleServiceCommissionType::class,
            'supervisor_commission_value' => 'decimal:6',
            'supervisor_commission_amount' => 'decimal:6',
            'status' => VehicleServiceJobStatus::class,
            'odometer_reading' => 'decimal:6',
            'subtotal' => 'decimal:6',
            'discount_total' => 'decimal:6',
            'tax_total' => 'decimal:6',
            'charge_total' => 'decimal:6',
            'grand_total' => 'decimal:6',
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
        ]);
    }

    public function scopeForContext(Builder $query, int $tenantId, ?int $organizationUnitId): Builder
    {
        $query->where('tenant_id', $tenantId);

        return $organizationUnitId === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $organizationUnitId);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id')->withTrashed();
    }

    public function billToCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'bill_to_customer_id')->withTrashed();
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'supervisor_employee_id');
    }

    public function inspection(): HasOne
    {
        return $this->hasOne(VehicleServiceInspection::class, 'vehicle_service_job_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(VehicleServiceJobLine::class, 'vehicle_service_job_id')->orderBy('line_number');
    }

    public function employeeAssignments(): HasMany
    {
        return $this->hasMany(VehicleServiceLineEmployee::class, 'vehicle_service_job_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VehicleServiceDocument::class, 'vehicle_service_job_id');
    }

    public function invoiceLinks(): HasMany
    {
        return $this->hasMany(VehicleServiceInvoiceLink::class, 'vehicle_service_job_id');
    }

    public function paymentLinks(): HasMany
    {
        return $this->hasMany(VehicleServicePaymentLink::class, 'vehicle_service_job_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(VehicleServiceStatusHistory::class, 'vehicle_service_job_id')->latest('changed_at');
    }
}
