<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;
use Modules\VehicleRental\Enums\RentalRateUnit;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalAgreementRateSnapshot extends CoreModel
{
    use ScopesRentalContext;

    protected $table = 'rental_agreement_rate_snapshots';

    protected function casts(): array
    {
        $decimal = [
            'base_rate', 'allowed_hours', 'allowed_km', 'extra_hour_rate', 'extra_km_rate',
            'overtime_rate', 'double_overtime_rate', 'night_shift_rate', 'weekend_rate',
            'holiday_rate', 'driver_rate', 'outstation_rate', 'day_out_rate', 'night_out_rate',
            'fuel_rate', 'waiting_hour_rate',
        ];
        $casts = [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'agreement_id' => 'integer',
            'rate_unit' => RentalRateUnit::class,
            'tax_profile_id' => 'integer',
            'currency_id' => 'integer',
        ];
        foreach ($decimal as $column) {
            $casts[$column] = 'decimal:6';
        }

        return $casts;
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(RentalAgreement::class, 'agreement_id');
    }
}
