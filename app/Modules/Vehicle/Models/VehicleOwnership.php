<?php

declare(strict_types=1);

namespace Modules\Vehicle\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;
use Modules\Customer\Models\Customer;
use Modules\Vehicle\Enums\VehicleOwnershipType;

final class VehicleOwnership extends CoreModel
{
    protected $table = 'vehicle_ownerships';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'vehicle_id' => 'integer',
            'owner_id' => 'integer',
            'customer_id' => 'integer',
            'ownership_type' => VehicleOwnershipType::class,
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'is_current' => 'boolean',
        ]);
    }

    public function vehicle(): BelongsTo { return $this->belongsTo(Vehicle::class, 'vehicle_id'); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class, 'customer_id'); }
}
