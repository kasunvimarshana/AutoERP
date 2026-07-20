<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\VehicleRental\Enums\RentalRateCode;
use Modules\VehicleRental\Enums\RentalRateUnit;

final class RentalCalculationLine extends TenantOwnedModel
{
    protected $table = 'vehicle_rental_calculation_lines';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'calculation_id' => 'integer',
            'rate_line_id' => 'integer',
            'line_number' => 'integer',
            'rate_code' => RentalRateCode::class,
            'unit' => RentalRateUnit::class,
            'quantity' => 'decimal:6',
            'unit_rate' => 'decimal:6',
            'line_total' => 'decimal:6',
            'is_taxable' => 'boolean',
        ]);
    }

    public function calculation(): BelongsTo
    {
        return $this->belongsTo(RentalCalculation::class, 'calculation_id');
    }

    public function rateLine(): BelongsTo
    {
        return $this->belongsTo(RentalRateLine::class, 'rate_line_id');
    }
}
