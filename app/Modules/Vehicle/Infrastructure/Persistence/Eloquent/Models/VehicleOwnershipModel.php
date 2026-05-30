<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class VehicleOwnershipModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'vehicle_ownerships';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'vehicle_id' => 'integer',
            'owner_id' => 'integer',
            'party_id' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_current' => 'boolean',
        ]);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'vehicle_id');
    }
}
