<?php

declare(strict_types=1);

namespace Modules\Vehicle\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\TenantOwnedModel;

final class VehicleMake extends TenantOwnedModel
{
    use SoftDeletes;

    protected $table = 'vehicle_makes';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'is_active' => 'boolean',
        ]);
    }

    public function models(): HasMany { return $this->hasMany(VehicleModel::class, 'vehicle_make_id'); }
    public function vehicles(): HasMany { return $this->hasMany(Vehicle::class, 'vehicle_make_id'); }
}
