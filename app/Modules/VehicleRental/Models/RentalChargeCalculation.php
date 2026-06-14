<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Core\Models\CoreModel;
use Modules\VehicleRental\Enums\RentalChargeCalculationType;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalChargeCalculation extends CoreModel
{
    use ScopesRentalContext;

    protected $table = 'rental_charge_calculations';

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'agreement_id' => 'integer',
            'source_id' => 'integer',
            'calculation_type' => RentalChargeCalculationType::class,
            'quantity' => 'decimal:6',
            'rate' => 'decimal:6',
            'amount' => 'decimal:6',
        ];
    }

    public function agreement(): BelongsTo { return $this->belongsTo(RentalAgreement::class, 'agreement_id'); }
    public function charge(): HasOne { return $this->hasOne(RentalCharge::class, 'charge_calculation_id'); }
}
