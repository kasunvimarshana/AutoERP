<?php

namespace App\Modules\Core\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Currencie extends BaseModel
{
    protected $table = 'currencies';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'symbol',
        'is_base',
        'decimal_places',
        'is_active'
    ];

    protected $casts = [
        'is_base' => 'boolean',
        'is_active' => 'boolean'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Tenant::class, 'tenant_id');
    }
}
