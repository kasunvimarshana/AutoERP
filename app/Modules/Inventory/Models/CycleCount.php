<?php

namespace App\Modules\Inventory\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CycleCount extends BaseModel
{
    protected $table = 'cycle_counts';

    protected $fillable = [
        'tenant_id',
        'warehouse_id',
        'reference',
        'status',
        'scheduled_date',
        'completed_date',
        'notes',
        'created_by',
        'created_at'
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_date' => 'date',
        'created_at' => 'datetime'
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
