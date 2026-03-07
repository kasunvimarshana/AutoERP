<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'category_id',
        'sku',
        'name',
        'description',
        'price',
        'cost_price',
        'currency',
        'unit',
        'weight',
        'dimensions',
        'status',
        'is_active',
        'metadata',
        'tags',
        'images',
    ];

    protected $casts = [
        'price'       => 'decimal:4',
        'cost_price'  => 'decimal:4',
        'weight'      => 'decimal:3',
        'dimensions'  => 'array',
        'metadata'    => 'array',
        'tags'        => 'array',
        'images'      => 'array',
        'is_active'   => 'boolean',
        'deleted_at'  => 'datetime',
    ];

    protected $hidden = [];

    /*
    |--------------------------------------------------------------------------
    | Global Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Apply tenant scope if tenant_id is set in the request context.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = app('tenant_id', null);
            if ($tenantId) {
                $builder->where('products.tenant_id', $tenantId);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Local Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    public function getFormattedPriceAttribute(): string
    {
        return number_format((float) $this->price, 2).' '.($this->currency ?? 'USD');
    }
}
