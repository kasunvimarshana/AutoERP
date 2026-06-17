<?php

declare(strict_types=1);

namespace Modules\Vehicle\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\CoreModel;

final class VehicleModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'vehicle_models';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'vehicle_make_id' => 'integer',
            'year_from' => 'integer',
            'year_to' => 'integer',
            'is_active' => 'boolean',
        ]);
    }

    public function make(): BelongsTo { return $this->belongsTo(VehicleMake::class, 'vehicle_make_id'); }
    public function vehicles(): HasMany { return $this->hasMany(Vehicle::class, 'vehicle_model_id'); }
}
