<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\Constants\AgreementFields;

final class BillingCycleChart extends TenantOwnedModel
{
    protected $table = 'vehicle_rental_billing_cycle_charts';

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'commercial_km' => 'decimal:'.AgreementFields::DECIMAL_SCALE,
            'normal_ot_minutes' => 'integer',
            'double_ot_minutes' => 'integer',
            'night_outs' => 'integer',
            'tolls_amount' => 'decimal:'.AgreementFields::DECIMAL_SCALE,
            'is_replacement' => 'boolean',
        ]);
    }

    public function billingCycle(): BelongsTo
    {
        return $this->belongsTo(BillingCycle::class, 'billing_cycle_id');
    }

    public function runningChart(): BelongsTo
    {
        return $this->belongsTo(RunningChart::class, 'running_chart_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id')->withTrashed();
    }
}
