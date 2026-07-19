<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\VehicleRental\Enums\RentalCustodyEventType;

final class RentalCustodyEvent extends TenantOwnedModel
{
    protected $table = 'vehicle_rental_custody_events';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'assignment_id' => 'integer',
            'event_type' => RentalCustodyEventType::class,
            'event_at' => 'datetime',
            'odometer' => 'decimal:6',
            'created_by' => 'integer',
        ]);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(RentalAssignment::class, 'assignment_id');
    }
}
