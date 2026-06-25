<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Core\Models\CoreModel;
use Modules\Customer\Models\Customer;
use Modules\Supplier\Models\Supplier;
use Modules\VehicleRental\Enums\RentalFinancialSide;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalUsageContext extends CoreModel
{
    use ScopesRentalContext;
    protected $table = 'rental_usage_contexts';
    protected $guarded = ['id'];
    protected function casts(): array { return ['row_version'=>'integer','tenant_id'=>'integer','organization_unit_id'=>'integer','usage_log_id'=>'integer','financial_side'=>RentalFinancialSide::class,'agreement_id'=>'integer','vehicle_allocation_id'=>'integer','rate_version_id'=>'integer','customer_id'=>'integer','supplier_id'=>'integer','currency_id'=>'integer','metadata'=>'array']; }
    public function usageLog(): BelongsTo { return $this->belongsTo(RentalUsageLog::class, 'usage_log_id'); }
    public function agreement(): BelongsTo { return $this->belongsTo(RentalAgreement::class, 'agreement_id'); }
    public function allocation(): BelongsTo { return $this->belongsTo(RentalVehicleAllocation::class, 'vehicle_allocation_id'); }
    public function rateVersion(): BelongsTo { return $this->belongsTo(RentalAgreementRateVersion::class, 'rate_version_id'); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class, 'customer_id'); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class, 'supplier_id'); }
    public function currency(): BelongsTo { return $this->belongsTo(CurrencyModel::class, 'currency_id'); }
    public function calculationLines(): HasMany { return $this->hasMany(RentalCalculationLine::class, 'usage_context_id'); }
}
