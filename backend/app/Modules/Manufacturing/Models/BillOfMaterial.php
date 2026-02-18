<?php

namespace App\Modules\Manufacturing\Models;

use App\Models\Tenant;
use App\Modules\Inventory\Models\Product;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BillOfMaterial extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\Manufacturing\BillOfMaterialFactory::new();
    }

    protected $fillable = [
        'tenant_id',
        'product_id',
        'bom_number',
        'version',
        'is_active',
        'effective_date',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'effective_date' => 'date',
        'version' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillOfMaterialItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }
}
