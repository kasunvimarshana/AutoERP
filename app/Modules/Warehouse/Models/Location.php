<?php

namespace App\Modules\Warehouse\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Location extends BaseModel
{
    protected $table = 'locations';

    protected $fillable = [
        'tenant_id',
        'warehouse_id',
        'parent_id',
        'code',
        'name',
        'type',
        'level',
        'path',
        'capacity',
        'is_pickable',
        'is_receivable',
        'is_active',
        'barcode'
    ];

    protected $casts = [
        'level' => 'integer',
        'is_pickable' => 'boolean',
        'is_receivable' => 'boolean',
        'is_active' => 'boolean'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Tenant::class, 'tenant_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Warehouse\Models\Warehouse::class, 'warehouse_id');
    }
}
