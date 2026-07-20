<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\VehicleRental\Enums\RentalCalculationSide;

final class RentalCalculationSource extends TenantOwnedModel
{
    protected $table = 'vehicle_rental_calculation_sources';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'calculation_id' => 'integer',
            'running_chart_id' => 'integer',
            'side' => RentalCalculationSide::class,
            'active_marker' => 'boolean',
        ]);
    }

    public function calculation(): BelongsTo
    {
        return $this->belongsTo(RentalCalculation::class, 'calculation_id');
    }

    public function runningChart(): BelongsTo
    {
        return $this->belongsTo(RentalRunningChart::class, 'running_chart_id');
    }
}
