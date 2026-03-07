<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class InventoryReservation extends Model
{
    use HasFactory;

    protected $table = 'inventory_reservations';
    public    $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'inventory_item_id', 'order_id', 'saga_id',
        'quantity', 'status', 'expires_at',
    ];

    protected $casts = [
        'quantity'   => 'integer',
        'expires_at' => 'datetime',
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

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'confirmed'])
                     ->where(function ($q) {
                         $q->whereNull('expires_at')
                           ->orWhere('expires_at', '>', now());
                     });
    }

    public function scopeExpired($query)
    {
        return $query->whereIn('status', ['pending', 'confirmed'])
                     ->whereNotNull('expires_at')
                     ->where('expires_at', '<=', now());
    }

    public function scopeBySaga($query, string $sagaId)
    {
        return $query->where('saga_id', $sagaId);
    }

    public function scopeByOrder($query, string $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    // -------------------------------------------------------------------------
    // Methods
    // -------------------------------------------------------------------------

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function confirm(): void
    {
        $this->update(['status' => 'confirmed']);
    }

    public function release(): void
    {
        $this->update(['status' => 'released']);
    }

    public function fulfill(): void
    {
        $this->update(['status' => 'fulfilled']);
    }
}
