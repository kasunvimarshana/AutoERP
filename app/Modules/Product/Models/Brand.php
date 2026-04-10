<?php

namespace App\Modules\Product\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Brand extends BaseModel
{
    protected $table = 'brands';

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'logo_url',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Tenant::class, 'tenant_id');
    }
}
