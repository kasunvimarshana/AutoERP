<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class VehicleServiceLaborAssignment extends Model
{
    use HasTenantAndOrganization;

    protected $table = 'vehicle_service_labor_assignments';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'hours_worked' => 'decimal:4',
            'hourly_rate' => 'decimal:4',
            'incentive_value' => 'decimal:4',
            'incentive_amount' => 'decimal:4',
        ];
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(VehicleServiceJobCard::class, 'job_card_id');
    }

    public function laborItem(): BelongsTo
    {
        return $this->belongsTo(VehicleServiceLaborItem::class, 'labor_item_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\HR\\Infrastructure\\Persistence\\Eloquent\\Models\\Employee',
            'employee_id'
        );
    }
}
