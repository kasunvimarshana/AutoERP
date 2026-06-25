<?php

declare(strict_types=1);

namespace Modules\Vehicle\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;
use Modules\Vehicle\Enums\VehicleAttributeDataType;

final class VehicleAttribute extends CoreModel
{
    protected $table = 'vehicle_attributes';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'vehicle_id' => 'integer',
            'data_type' => VehicleAttributeDataType::class,
            'sort_order' => 'integer',
        ]);
    }

    public function vehicle(): BelongsTo { return $this->belongsTo(Vehicle::class, 'vehicle_id'); }
}
