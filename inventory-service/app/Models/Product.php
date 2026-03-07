<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Ramsey\Uuid\Uuid;

class Product extends Model
{
    use HasFactory;

    protected $keyType    = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id',
        'sku',
        'name',
        'description',
        'price',
        'stock_quantity',
        'reserved_quantity',
    ];

    protected $casts = [
        'price'             => 'decimal:2',
        'stock_quantity'    => 'integer',
        'reserved_quantity' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Product $product): void {
            if (empty($product->id)) {
                $product->id = Uuid::uuid4()->toString();
            }
            if (! isset($product->reserved_quantity)) {
                $product->reserved_quantity = 0;
            }
        });
    }

    /**
     * Get the number of units available for reservation.
     */
    public function getAvailableStock(): int
    {
        return max(0, $this->stock_quantity - $this->reserved_quantity);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(InventoryReservation::class, 'product_id', 'id');
    }
}
