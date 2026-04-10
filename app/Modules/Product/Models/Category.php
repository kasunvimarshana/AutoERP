<?php

namespace App\Modules\Product\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Category extends BaseModel
{
    protected $table = 'categories';

    protected $fillable = [
        'tenant_id',
        'parent_id',
        'name',
        'slug',
        'level',
        'path',
        'image_url',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'level' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Tenant::class, 'tenant_id');
    }
}
