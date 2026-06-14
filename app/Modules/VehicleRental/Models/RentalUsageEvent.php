<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;
use Modules\VehicleRental\Enums\RentalUsageEventType;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalUsageEvent extends CoreModel
{
    use ScopesRentalContext;

    protected $table = 'rental_usage_events';

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'usage_log_id' => 'integer',
            'event_type' => RentalUsageEventType::class,
            'quantity' => 'decimal:6',
            'created_by' => 'integer',
        ];
    }

    public function usageLog(): BelongsTo
    {
        return $this->belongsTo(RentalUsageLog::class, 'usage_log_id');
    }
}
