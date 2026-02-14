<?php

namespace App\Modules\Inventory\Models;

use App\Core\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Brand Model
 * 
 * Represents a product brand
 */
class Brand extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'name',
        'slug',
        'logo',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [];

    protected static function newFactory()
    {
        return \Database\Factories\BrandFactory::new();
    }

    /**
     * Get products for this brand
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
