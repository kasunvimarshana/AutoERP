<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InventoryItem extends Model
{
    use HasFactory;

    protected $table = 'inventory_items';
    public    $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'product_id', 'tenant_id', 'warehouse_id',
        'quantity_available', 'quantity_reserved', 'quantity_sold',
        'reorder_level', 'max_stock_level', 'unit_of_measure',
    ];

    protected $casts = [
        'quantity_available' => 'integer',
        'quantity_reserved'  => 'integer',
        'quantity_sold'      => 'integer',
        'reorder_level'      => 'integer',
        'max_stock_level'    => 'integer',
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

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function reservations()
    {
        return $this->hasMany(InventoryReservation::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeByTenant($query, string $tenantId)
    {
        return $query->where('inventory_items.tenant_id', $tenantId);
    }

    public function scopeByWarehouse($query, string $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('quantity_available <= reorder_level');
    }

    // -------------------------------------------------------------------------
    // Methods
    // -------------------------------------------------------------------------

    /**
     * Atomically move `quantity` units from available → reserved.
     * Uses a conditional UPDATE to prevent race conditions.
     */
    public function reserve(int $quantity): bool
    {
        if (!$this->isAvailable($quantity)) {
            return false;
        }

        $affected = DB::table('inventory_items')
            ->where('id', $this->id)
            ->where('quantity_available', '>=', $quantity)
            ->update([
                'quantity_available' => DB::raw("quantity_available - {$quantity}"),
                'quantity_reserved'  => DB::raw("quantity_reserved + {$quantity}"),
                'updated_at'         => now(),
            ]);

        if ($affected > 0) {
            $this->refresh();
            return true;
        }

        return false;
    }

    /**
     * Atomically move `quantity` units from reserved → available.
     */
    public function releaseQuantity(int $quantity): void
    {
        DB::table('inventory_items')
            ->where('id', $this->id)
            ->update([
                'quantity_available' => DB::raw("quantity_available + {$quantity}"),
                'quantity_reserved'  => DB::raw("GREATEST(0, quantity_reserved - {$quantity})"),
                'updated_at'         => now(),
            ]);

        $this->refresh();
    }

    /**
     * Atomically move `quantity` units from reserved → sold (fulfillment).
     */
    public function fulfillQuantity(int $quantity): void
    {
        DB::table('inventory_items')
            ->where('id', $this->id)
            ->update([
                'quantity_reserved' => DB::raw("GREATEST(0, quantity_reserved - {$quantity})"),
                'quantity_sold'     => DB::raw("quantity_sold + {$quantity}"),
                'updated_at'        => now(),
            ]);

        $this->refresh();
    }

    public function isAvailable(int $quantity): bool
    {
        return $this->quantity_available >= $quantity;
    }

    public function availableQuantity(): int
    {
        return $this->quantity_available;
    }
}
