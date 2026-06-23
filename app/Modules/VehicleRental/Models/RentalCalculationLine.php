<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Tax\Models\TaxGroup;
use Modules\VehicleRental\Enums\RentalCalculationLineStatus;
use Modules\VehicleRental\Enums\RentalRateComponentCode;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalCalculationLine extends TenantOwnedModel
{
    use ScopesRentalContext;
    protected $table = 'rental_calculation_lines';
    protected $guarded = ['id'];
    protected function casts(): array { return ['row_version'=>'integer','tenant_id'=>'integer','organization_unit_id'=>'integer','calculation_run_id'=>'integer','line_number'=>'integer','usage_context_id'=>'integer','expense_allocation_id'=>'integer','custody_event_item_id'=>'integer','source_id'=>'integer','component_code'=>RentalRateComponentCode::class,'measured_quantity'=>'decimal:6','allowed_quantity'=>'decimal:6','chargeable_quantity'=>'decimal:6','rate'=>'decimal:6','multiplier'=>'decimal:6','net_amount'=>'decimal:6','discount_amount'=>'decimal:6','tax_group_id'=>'integer','tax_amount'=>'decimal:6','withholding_amount'=>'decimal:6','total_amount'=>'decimal:6','rule_snapshot'=>'array','status'=>RentalCalculationLineStatus::class,'metadata'=>'array']; }
    public function run(): BelongsTo { return $this->belongsTo(RentalCalculationRun::class, 'calculation_run_id'); }
    public function usageContext(): BelongsTo { return $this->belongsTo(RentalUsageContext::class, 'usage_context_id'); }
    public function expenseAllocation(): BelongsTo { return $this->belongsTo(RentalExpenseAllocation::class, 'expense_allocation_id'); }
    public function custodyEventItem(): BelongsTo { return $this->belongsTo(RentalCustodyEventItem::class, 'custody_event_item_id'); }
    public function taxGroup(): BelongsTo { return $this->belongsTo(TaxGroup::class, 'tax_group_id'); }
}
