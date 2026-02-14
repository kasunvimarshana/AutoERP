<?php

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Brand Model
 *
 * Product brand/manufacturer management.
 */
class Brand extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'description',
        'logo',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all products of this brand.
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Scope active brands.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
