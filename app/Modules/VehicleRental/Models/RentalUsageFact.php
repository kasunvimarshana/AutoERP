<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\VehicleRental\Enums\RentalFinancialSide;
use Modules\VehicleRental\Enums\RentalUsageFactStatus;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalUsageFact extends TenantOwnedModel
{
    use ScopesRentalContext;

    protected $table = 'rental_usage_facts';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'usage_context_id' => 'integer',
            'usage_log_id' => 'integer',
            'financial_side' => RentalFinancialSide::class,
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'start_odometer' => 'decimal:6',
            'end_odometer' => 'decimal:6',
            'commercial_distance_km' => 'decimal:6',
            'working_minutes' => 'integer',
            'normal_overtime_minutes' => 'integer',
            'double_overtime_minutes' => 'integer',
            'triple_overtime_minutes' => 'integer',
            'night_out_count' => 'decimal:6',
            'status' => RentalUsageFactStatus::class,
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'reversed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function context(): BelongsTo
    {
        return $this->belongsTo(RentalUsageContext::class, 'usage_context_id');
    }

    public function usageLog(): BelongsTo
    {
        return $this->belongsTo(RentalUsageLog::class, 'usage_log_id');
    }
}
