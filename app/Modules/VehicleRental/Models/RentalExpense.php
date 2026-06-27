<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Hr\Models\HrEmployee;
use Modules\Supplier\Models\Supplier;
use Modules\Tax\Models\TaxGroup;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\Enums\RentalExpenseStatus;
use Modules\VehicleRental\Enums\RentalExpenseType;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalExpense extends TenantOwnedModel
{
    use ScopesRentalContext;
    protected $table = 'rental_expenses';
    protected $guarded = ['id'];
    protected function casts(): array { return ['row_version'=>'integer','tenant_id'=>'integer','organization_unit_id'=>'integer','agreement_id'=>'integer','vehicle_allocation_id'=>'integer','usage_log_id'=>'integer','vehicle_id'=>'integer','supplier_id'=>'integer','employee_id'=>'integer','expense_type'=>RentalExpenseType::class,'expense_date'=>'date','currency_id'=>'integer','net_amount'=>'decimal:6','tax_group_id'=>'integer','tax_amount'=>'decimal:6','gross_amount'=>'decimal:6','source_document_id'=>'integer','status'=>RentalExpenseStatus::class,'metadata'=>'array','submitted_at'=>'datetime','approved_at'=>'datetime','rejected_at'=>'datetime','reversed_at'=>'datetime']; }
    public function agreement(): BelongsTo { return $this->belongsTo(RentalAgreement::class, 'agreement_id'); }
    public function allocation(): BelongsTo { return $this->belongsTo(RentalVehicleAllocation::class, 'vehicle_allocation_id'); }
    public function usageLog(): BelongsTo { return $this->belongsTo(RentalUsageLog::class, 'usage_log_id'); }
    public function vehicle(): BelongsTo { return $this->belongsTo(Vehicle::class, 'vehicle_id'); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class, 'supplier_id'); }
    public function employee(): BelongsTo { return $this->belongsTo(HrEmployee::class, 'employee_id'); }
    public function currency(): BelongsTo { return $this->belongsTo(CurrencyModel::class, 'currency_id'); }
    public function taxGroup(): BelongsTo { return $this->belongsTo(TaxGroup::class, 'tax_group_id'); }
    public function allocations(): HasMany { return $this->hasMany(RentalExpenseAllocation::class, 'expense_id')->orderBy('sequence'); }
    public function documents(): HasMany { return $this->hasMany(RentalExpenseDocument::class, 'expense_id')->latest('id'); }
}
