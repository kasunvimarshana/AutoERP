<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'products';
    public    $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'tenant_id', 'sku', 'name', 'description', 'category',
        'unit_price', 'currency', 'is_active', 'metadata',
    ];

    protected $casts = [
        'unit_price' => 'decimal:4',
        'is_active'  => 'boolean',
        'metadata'   => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function inventoryItems()
    {
        return $this->hasMany(InventoryItem::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeByTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    // -------------------------------------------------------------------------
    // Methods
    // -------------------------------------------------------------------------

    public function isAvailable(): bool
    {
        return $this->is_active
            && $this->inventoryItems()->where('quantity_available', '>', 0)->exists();
    }
}
