<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Customer\Models\Customer;
use Modules\Hr\Models\HrEmployee;
use Modules\Supplier\Models\Supplier;
use Modules\Tax\Models\TaxGroup;
use Modules\VehicleRental\Enums\RentalExpenseAllocationType;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalExpenseAllocation extends TenantOwnedModel
{
    use ScopesRentalContext;
    protected $table = 'rental_expense_allocations';
    protected $guarded = ['id'];
    protected function casts(): array { return ['row_version'=>'integer','tenant_id'=>'integer','organization_unit_id'=>'integer','expense_id'=>'integer','sequence'=>'integer','allocation_type'=>RentalExpenseAllocationType::class,'target_agreement_id'=>'integer','target_vehicle_allocation_id'=>'integer','customer_id'=>'integer','supplier_id'=>'integer','employee_id'=>'integer','net_amount'=>'decimal:6','tax_group_id'=>'integer','tax_amount'=>'decimal:6','withholding_amount'=>'decimal:6','markup_amount'=>'decimal:6','total_amount'=>'decimal:6','metadata'=>'array']; }
    public function expense(): BelongsTo { return $this->belongsTo(RentalExpense::class, 'expense_id'); }
    public function targetAgreement(): BelongsTo { return $this->belongsTo(RentalAgreement::class, 'target_agreement_id'); }
    public function targetAllocation(): BelongsTo { return $this->belongsTo(RentalVehicleAllocation::class, 'target_vehicle_allocation_id'); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class, 'customer_id'); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class, 'supplier_id'); }
    public function employee(): BelongsTo { return $this->belongsTo(HrEmployee::class, 'employee_id'); }
    public function taxGroup(): BelongsTo { return $this->belongsTo(TaxGroup::class, 'tax_group_id'); }
}
