<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class UnitOfMeasureModel extends BaseModel
{
    use HasTenant, HasUuid;

    protected $table = 'units_of_measure';

    protected $fillable = [
        'uuid', 'tenant_id', 'name', 'abbreviation', 'type',
        'base_unit_id', 'conversion_factor', 'is_base', 'is_active',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:10',
        'is_base'           => 'boolean',
        'is_active'         => 'boolean',
    ];

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(self::class, 'base_unit_id');
    }

    public function derivedUnits(): HasMany
    {
        return $this->hasMany(self::class, 'base_unit_id');
    }
}
