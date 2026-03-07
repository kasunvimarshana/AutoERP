<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends TenantAwareModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'category_id',
        'name',
        'sku',
        'description',
        'price',
        'cost',
        'weight',
        'dimensions',
        'images',
        'tags',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'weight' => 'decimal:3',
        'dimensions' => 'array',
        'images' => 'array',
        'tags' => 'array',
        'is_active' => 'boolean',
    ];

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
