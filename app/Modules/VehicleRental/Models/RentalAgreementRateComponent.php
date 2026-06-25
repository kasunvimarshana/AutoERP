<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;
use Modules\Tax\Models\TaxGroup;
use Modules\Vehicle\Models\VehicleCategory;
use Modules\VehicleRental\Enums\RentalRateComponentCode;
use Modules\VehicleRental\Enums\RentalRateUnit;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalAgreementRateComponent extends CoreModel
{
    use ScopesRentalContext;
    protected $table = 'rental_agreement_rate_components';
    protected $guarded = ['id'];
    protected function casts(): array { return ['row_version'=>'integer','tenant_id'=>'integer','organization_unit_id'=>'integer','rate_version_id'=>'integer','vehicle_category_id'=>'integer','component_code'=>RentalRateComponentCode::class,'unit'=>RentalRateUnit::class,'included_quantity'=>'decimal:6','rate'=>'decimal:6','multiplier'=>'decimal:6','minimum_amount'=>'decimal:6','maximum_amount'=>'decimal:6','tax_group_override_id'=>'integer','is_taxable'=>'boolean','calculation_order'=>'integer','metadata'=>'array']; }
    public function rateVersion(): BelongsTo { return $this->belongsTo(RentalAgreementRateVersion::class, 'rate_version_id'); }
    public function vehicleCategory(): BelongsTo { return $this->belongsTo(VehicleCategory::class, 'vehicle_category_id'); }
    public function taxGroupOverride(): BelongsTo { return $this->belongsTo(TaxGroup::class, 'tax_group_override_id'); }
}
