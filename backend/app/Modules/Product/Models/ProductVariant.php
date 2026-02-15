<?php

namespace App\Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantScoped;
use App\Core\Traits\Auditable;
use App\Models\User;

class ProductVariant extends Model
{
    use HasFactory, SoftDeletes, HasUuid, TenantScoped, Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'tenant_id',
        'product_id',
        'sku',
        'name',
        'description',
        'buying_price',
        'selling_price',
        'mrp',
        'attributes',
        'images',
        'is_active',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'buying_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'mrp' => 'decimal:2',
        'attributes' => 'array',
        'images' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the product that owns the variant.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user who created the variant.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the variant.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if variant is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->is_active && $this->product && $this->product->isActive();
    }

    /**
     * Get the full name including product name and variant attributes.
     *
     * @return string
     */
    public function getFullName(): string
    {
        if (!$this->product) {
            return $this->name;
        }
        
        $name = $this->product->name;
        if ($this->name) {
            $name .= ' - ' . $this->name;
        }
        
        return $name;
    }

    /**
     * Get variant attribute value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getAttribute($key, $default = null)
    {
        $attributes = $this->attributes ?? [];
        return $attributes[$key] ?? $default;
    }
}
