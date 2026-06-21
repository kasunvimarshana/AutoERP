<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\CoreModel;
use Modules\VehicleRental\Enums\RentalBillingPeriodStatus;
use Modules\VehicleRental\Enums\RentalFinancialSide;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalBillingPeriod extends CoreModel
{
    use ScopesRentalContext;
    protected $table = 'rental_billing_periods';
    protected $guarded = ['id'];
    protected function casts(): array { return ['row_version'=>'integer','tenant_id'=>'integer','organization_unit_id'=>'integer','agreement_id'=>'integer','financial_side'=>RentalFinancialSide::class,'rate_version_id'=>'integer','period_start'=>'datetime','period_end'=>'datetime','period_sequence'=>'integer','status'=>RentalBillingPeriodStatus::class,'is_final'=>'boolean','metadata'=>'array','closed_at'=>'datetime','reopened_at'=>'datetime']; }
    public function agreement(): BelongsTo { return $this->belongsTo(RentalAgreement::class, 'agreement_id'); }
    public function rateVersion(): BelongsTo { return $this->belongsTo(RentalAgreementRateVersion::class, 'rate_version_id'); }
    public function runs(): HasMany { return $this->hasMany(RentalCalculationRun::class, 'billing_period_id')->orderBy('run_version'); }
}
