<?php

namespace App\Modules\Product\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProductAttribute extends BaseModel
{
    protected $table = 'product_attributes';

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'is_required'
    ];

    protected $casts = [
        
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Tenant::class, 'tenant_id');
    }
}
