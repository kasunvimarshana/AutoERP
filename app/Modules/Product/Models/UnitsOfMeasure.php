<?php

namespace App\Modules\Product\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UnitsOfMeasure extends BaseModel
{
    protected $table = 'units_of_measure';

    protected $fillable = [
        'tenant_id',
        'name',
        'symbol',
        'type',
        'is_base'
    ];

    protected $casts = [
        'is_base' => 'boolean'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Tenant::class, 'tenant_id');
    }
}
