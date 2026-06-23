<?php

declare(strict_types=1);

namespace Modules\Vehicle\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\TenantOwnedModel;

final class VehicleType extends TenantOwnedModel
{
    use SoftDeletes;

    protected $table = 'vehicle_types';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);
    }

    public function vehicles(): HasMany { return $this->hasMany(Vehicle::class, 'vehicle_type_id'); }
}
