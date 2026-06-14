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
            'agreement_id' => 'integer',
            'event_type' => RentalUsageEventType::class,
            'quantity' => 'decimal:6',
            'rate_snapshot' => 'decimal:6',
            'amount' => 'decimal:6',
        ];
    }

    public function usageLog(): BelongsTo { return $this->belongsTo(RentalUsageLog::class, 'usage_log_id'); }
    public function agreement(): BelongsTo { return $this->belongsTo(RentalAgreement::class, 'agreement_id'); }
}
