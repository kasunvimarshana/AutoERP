<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Tax\Models\TaxGroup;
use Modules\VehicleRental\Enums\RentalBillingBasis;
use Modules\VehicleRental\Enums\RentalBillingCycle;
use Modules\VehicleRental\Enums\RentalExcessKmMethod;
use Modules\VehicleRental\Enums\RentalMode;
use Modules\VehicleRental\Enums\RentalProrationRule;
use Modules\VehicleRental\Enums\RentalRateVersionStatus;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalAgreementRateVersion extends TenantOwnedModel
{
    use ScopesRentalContext;

    protected $table = 'rental_agreement_rate_versions';
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::deleting(static function (RentalAgreementRateVersion $version): void {
            $status = $version->status instanceof RentalRateVersionStatus
                ? $version->status
                : RentalRateVersionStatus::from((string) $version->status);

            if ($status !== RentalRateVersionStatus::Draft) {
                throw new LogicException('Only draft rental agreement rate versions can be deleted. Use cancellation or supersession for rate history.');
            }
        });
    }

    protected function casts(): array { return [
        'row_version'=>'integer','tenant_id'=>'integer','organization_unit_id'=>'integer','agreement_id'=>'integer','version_number'=>'integer',
        'effective_from'=>'datetime','effective_to'=>'datetime','driver_mode'=>RentalMode::class,'billing_cycle'=>RentalBillingCycle::class,
        'billing_basis'=>RentalBillingBasis::class,'proration_rule'=>RentalProrationRule::class,'excess_km_method'=>RentalExcessKmMethod::class,
        'included_km'=>'decimal:6','included_hours'=>'decimal:6','weekday_included_minutes'=>'integer','saturday_included_minutes'=>'integer','holiday_included_minutes'=>'integer',
        'currency_id'=>'integer','tax_group_id'=>'integer','withholding_tax_group_id'=>'integer','status'=>RentalRateVersionStatus::class,'metadata'=>'array','approved_at'=>'datetime']; }
    public function agreement(): BelongsTo { return $this->belongsTo(RentalAgreement::class, 'agreement_id'); }
    public function currency(): BelongsTo { return $this->belongsTo(CurrencyModel::class, 'currency_id'); }
    public function taxGroup(): BelongsTo { return $this->belongsTo(TaxGroup::class, 'tax_group_id'); }
    public function withholdingTaxGroup(): BelongsTo { return $this->belongsTo(TaxGroup::class, 'withholding_tax_group_id'); }
    public function components(): HasMany { return $this->hasMany(RentalAgreementRateComponent::class, 'rate_version_id')->orderBy('calculation_order'); }
}
