<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\VehicleRental\Enums\RentalUsageEventApplicability;
use Modules\VehicleRental\Enums\RentalUsageEventType;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalUsageEvent extends TenantOwnedModel
{
    use ScopesRentalContext;

    protected $table = 'rental_usage_events';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'usage_log_id' => 'integer',
            'sequence' => 'integer',
            'event_type' => RentalUsageEventType::class,
            'applicability' => RentalUsageEventApplicability::class,
            'occurred_at' => 'datetime',
            'quantity' => 'decimal:6',
            'metadata' => 'array',
        ];
    }

    public function usageLog(): BelongsTo
    {
        return $this->belongsTo(RentalUsageLog::class, 'usage_log_id');
    }
}
