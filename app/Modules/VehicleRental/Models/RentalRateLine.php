<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\VehicleRental\Enums\RentalRateCode;
use Modules\VehicleRental\Enums\RentalRateUnit;

final class RentalRateLine extends TenantOwnedModel
{
    protected $table = 'vehicle_rental_rate_lines';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'rate_version_id' => 'integer',
            'line_number' => 'integer',
            'rate_code' => RentalRateCode::class,
            'unit' => RentalRateUnit::class,
            'rate' => 'decimal:6',
            'is_taxable' => 'boolean',
        ]);
    }

    public function rateVersion(): BelongsTo
    {
        return $this->belongsTo(RentalRateVersion::class, 'rate_version_id');
    }
}
